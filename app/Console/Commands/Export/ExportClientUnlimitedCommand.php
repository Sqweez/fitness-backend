<?php

namespace App\Console\Commands\Export;

use App\Models\Service;
use App\Models\ServiceSale;
use App\Vars\ExportDates;
use Illuminate\Console\Command;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

class ExportClientUnlimitedCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'export:client-unlimited';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Export clients unlimited';

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
        $date = new ExportDates();

        ini_set('memory_limit', '2000M');

        $template = IOFactory::load('excel/Импорт_абонементы_клиентов.xlsx');
        $currentSheet = $template->getActiveSheet();

        $recordsTotal = 0;

        ServiceSale::query()
            ->whereHas('sale', function ($q) use ($date) {
                return $q
                    ->whereDate('created_at', '>=', $date->start)
                    ->whereDate('created_at', '<=', $date->finish);
            })
            ->with(['sale' => function ($q) {
                return $q->where('club_id', 1)->with('client')->with('transaction')->with('user');
            }])
            ->whereHas('service', function ($q) {
                return $q->where('service_type_id', 1)->where('club_id', 1);
            })
            ->with(['service' => function ($q) {
                return $q->where('service_type_id', 1)->where('club_id', 1);
            }])
            ->chunk(1000, function ($services) use (&$recordsTotal, &$currentSheet) {
                $_services = $services->filter(function ($sale) {
                    return isset($sale->sale->client) && $sale->sale->client->name;
                });
                $_services = $_services->map(function (ServiceSale $sale) {
                    return [
                        'id' => '',
                        'id2' => '',
                        'phone' => $sale->sale->client->phone,
                        'client_fio' => $sale->sale->client->name,
                        'contract_name' => $sale->service->name,
                        'card' => $sale->sale->client->cached_pass,
                        'duration' => $sale->service->validity_days,
                        'duration_type' => 'день',
                        'create_date' => format_date($sale->created_at),
                        'payment_date' => format_date($sale->created_at),
                        'activation_date' => format_date($sale->activated_at),
                        'end_date' => format_date($sale->active_until),
                        'freeze' => '',
                        'guests' => '',
                        'visits_left' => '',
                        'price' => $sale->sale->transaction->amount * -1,
                        'amount_of_payments' => $sale->sale->transaction->amount * -1,
                        'payment_left' => 0,
                        'type_of_payment' => 'Наличные',
                        'manager' => '',
                    ];
                })->toArray();

                $currentSheet->fromArray($_services, null, 'A' . ($recordsTotal + 3), true);
                $recordsTotal += count($_services);
                $this->line($recordsTotal);
            });

        $recordsTotal = 0;
        $excelWriter = new Xlsx($template);
        $fileName = 'Импорт_абонементы_клиентов_' . 'СТУДИЯ' . '.xlsx';
        $path = "storage/excel/";
        \File::ensureDirectoryExists($path);
        $fullPath =  $path . $fileName;
        $excelWriter->save($fullPath);

        $excelWriter = null;

        ServiceSale::query()
            ->whereHas('sale', function ($q) use ($date) {
                return $q
                    ->whereDate('created_at', '>=', $date->start)
                    ->whereDate('created_at', '<=', $date->finish);
            })
            ->with(['sale' => function ($q) {
                return $q->where('club_id', [2, 3])->with('client')->with('transaction')->with('user');
            }])
            ->whereHas('service', function ($q) {
                return $q->where('service_type_id', 1)->where('club_id', [2, 3]);
            })
            ->with(['service' => function ($q) {
                return $q->where('service_type_id', 1)->where('club_id', [2, 3]);
            }])
            ->chunk(1000, function ($services) use (&$recordsTotal, &$currentSheet) {
                $_services = $services->filter(function ($sale) {
                    return isset($sale->sale->client) && $sale->sale->client->name;
                });
                $_services = $_services->map(function (ServiceSale $sale) {
                    return [
                        'id' => '',
                        'id2' => '',
                        'phone' => $sale->sale->client->phone,
                        'client_fio' => $sale->sale->client->name,
                        'contract_name' => $sale->service->name,
                        'card' => $sale->sale->client->cached_pass,
                        'duration' => $sale->service->validity_days,
                        'duration_type' => 'день',
                        'create_date' => format_date($sale->created_at),
                        'payment_date' => format_date($sale->created_at),
                        'activation_date' => format_date($sale->activated_at),
                        'end_date' => format_date($sale->active_until),
                        'freeze' => '',
                        'guests' => '',
                        'visits_left' => '',
                        'price' => $sale->sale->transaction->amount * -1,
                        'amount_of_payments' => $sale->sale->transaction->amount * -1,
                        'payment_left' => 0,
                        'type_of_payment' => 'Наличные',
                        'manager' => '',
                    ];
                })->toArray();

                $currentSheet->fromArray($_services, null, 'A' . ($recordsTotal + 3), true);
                $recordsTotal += count($_services);
                $this->line($recordsTotal);
            });

        $excelWriter = new Xlsx($template);
        $fileName = 'Импорт_абонементы_клиентов_' . 'АТРИУМ' . '.xlsx';
        $path = "storage/excel/";
        \File::ensureDirectoryExists($path);
        $fullPath =  $path . $fileName;
        $excelWriter->save($fullPath);
    }

    private function write($sheet, $template, $name, $input)
    {
        $sheet->fromArray($input, null, 'A3', true);

        $excelWriter = new Xlsx($template);
        $fileName = 'Импорт_абонементы_клиентов_' . $name . '.xlsx';
        $path = "storage/excel/";
        \File::ensureDirectoryExists($path);
        $fullPath =  $path . $fileName;
        $excelWriter->save($fullPath);
    }
}
