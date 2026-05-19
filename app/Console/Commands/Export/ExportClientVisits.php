<?php

namespace App\Console\Commands\Export;

use App\Models\SessionService;
use App\Vars\ExportDates;
use Illuminate\Console\Command;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

class ExportClientVisits extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'export:client-visits';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Export client visits';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        ini_set('memory_limit', '2000M');
        $this->do([1], 'СТУДИЯ');
        $this->do([2, 3], 'АТРИУМ');
    }

    private function do($clubIds, $name)
    {
        $dates = new ExportDates();
        $template = IOFactory::load('excel/Импорт_списания_услуг.xlsx');
        $currentSheet = $template->getActiveSheet();
        $recordsTotal = 0;
        SessionService::query()
            ->whereHas('session', function ($query) use ($clubIds, $dates) {
                return $query
                    ->whereDate('created_at', '>=', $dates->start)
                    ->whereDate('created_at', '<=', $dates->finish)
                    ->whereIn('club_id', $clubIds);
            })
            ->with(['session' => function ($q) use ($clubIds) {
                return $q
                    ->whereIn('club_id', $clubIds)
                    ->with('client:id,name');
            }])
            ->with('user')
            ->whereHas('service_sale', function ($query) {
                return $query->whereHas('service', function ($q) {
                    return $q
                        ->where('name', 'not like', '%test%')
                        ->where('name', 'not like', '%тест%')
                        ->where('name', '!=', '')
                        ->whereNotIn('id', [583, 1361, 1362, 1163, 1365, 1864, 1865, 204])
                        ->where('price', '>', 5)
                        ->where('service_type_id', 3);
                });
            })
            ->with('service_sale.service:id,name')
            ->chunk(1000, function ($services) use (&$currentSheet, &$recordsTotal) {
                $_services = $services
                    ->filter(function ($service) {
                        return isset($service->session->client);
                    })
                    ->map(function (SessionService $service) {
                        return [
                            'id' => '',
                            'phone' => $service->session->client->phone,
                            'client_fio' => $service->session->client->name,
                            'service_id' => null,
                            'service_name' => $service->service_sale->service->name,
                            'date_enter' => format_date($service->created_at),
                            'date_exit' => format_date($service->created_at),
                            'manager' => '',
                            'trainer' => null,
                        ];
                    })
                    ->toArray();

                $this->line($recordsTotal);
                $currentSheet->fromArray($_services, null, 'A' . ($recordsTotal + 3), true);
                $recordsTotal += count($_services);
            });

        $excelWriter = new Xlsx($template);
        $fileName = 'Импорт_списания_услуг_' . $name . '.xlsx';
        $path = "storage/excel/";
        \File::ensureDirectoryExists($path);
        $fullPath =  $path . $fileName;
        $excelWriter->save($fullPath);
    }
}
