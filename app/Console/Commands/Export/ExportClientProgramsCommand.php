<?php

namespace App\Console\Commands\Export;

use App\Models\ServiceSale;
use App\Vars\ExportDates;
use Illuminate\Console\Command;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

class ExportClientProgramsCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'export:clients-programs';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Export client programs';

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

        $template = IOFactory::load('excel/Импорт_услуги_клиентов.xlsx');
        $currentSheet = $template->getActiveSheet();
        $recordsTotal = 0;

        ServiceSale::query()
            ->whereHas('sale', function ($q) use ($date) {
                return $q
                    ->whereDate('created_at', '>=', $date->start)
                    ->whereDate('created_at', '<=', $date->finish);
            })
            ->with(['sale' => function ($q) {
                return $q
                    ->where('club_id', 1)
                    ->with('client')
                    ->with('transaction')
                    ->with('user')
                    ->has('transaction');
            }])
            ->whereHas('service', function ($q) {
                return $q
                    ->where('name', 'not like', '%test%')
                    ->where('name', 'not like', '%тест%')
                    ->where('name', '!=', '')
                    ->whereNotIn('id', [583, 1361, 1362, 1163, 1365, 1864, 1865, 204])
                    ->where('price', '>', 5)
                    ->where('service_type_id', 3)
                    ->where('club_id', 1);
            })
            ->with(['service' => function ($q) {
                return $q->where('service_type_id', 3)->where('club_id', 1);
            }])
            ->with('visits')
            ->with('penalties')
            ->chunk(1000, function ($sales) use (&$currentSheet, &$recordsTotal) {
                $_sales = $sales->filter(function ($sale) {
                    return isset($sale->sale->client);
                });
                $_sales = $_sales
                    ->map(function (ServiceSale $sale) {
                        return [
                            'id' => '',
                            'id2' => '',
                            'phone' => $sale->sale->client->phone,
                            'client_fio' => $sale->sale->client->name,
                            'contract_name' => $sale->service->name,
                            'create_date' => format_date($sale->created_at),
                            'payment_date' => format_date($sale->created_at),
                            'activation_date' => format_date($sale->activated_at),
                            'end_date' => format_date($sale->active_until),
                            'count' => 1,
                            'visits_left' => $sale->getRemainingVisitsAttribute(),
                            'price' => $sale->sale->transaction->amount * -1,
                            'amount_of_payments' => $sale->sale->transaction->amount * -1,
                            'payment_left' => 0,
                            'type_of_payment' => 'Наличные',
                            'manager' => '',
                        ];
                    })
                    ->toArray();

                $currentSheet->fromArray($_sales, null, 'A' . ($recordsTotal + 3), true);
                $recordsTotal += count($_sales);
                $this->line($recordsTotal);
            });


        $recordsTotal = 0;
        $excelWriter = new Xlsx($template);
        $fileName = 'Импорт_услуги_клиентов_' . 'СТУДИЯ' . '.xlsx';
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
                return $q->where('club_id', [2, 3])->with('client')->with('transaction')->with('user')->has('transaction');
            }])
            ->whereHas('service', function ($q) {
                return $q
                    ->where('name', 'not like', '%test%')
                    ->where('name', 'not like', '%тест%')
                    ->where('name', '!=', '')
                    ->whereNotIn('id', [583, 1361, 1362, 1163, 1365, 1864, 1865, 204])
                    ->where('price', '>', 5)
                    ->where('service_type_id', 3)
                    ->where('club_id', [2, 3]);
            })
            ->with(['service' => function ($q) {
                return $q->where('service_type_id', 3)->where('club_id', [2, 3]);
            }])
            ->with('visits')
            ->with('penalties')
            ->chunk(1000, function ($sales) use (&$currentSheet, &$recordsTotal) {
                $_sales = $sales->filter(function ($sale) {
                    return isset($sale->sale->client);
                });
                $_sales = $_sales
                    ->map(function (ServiceSale $sale) {
                        $this->line($sale->sale->client->name);
                        return [
                            'id' => '',
                            'id2' => '',
                            'phone' => $sale->sale->client->phone,
                            'client_fio' => $sale->sale->client->name,
                            'contract_name' => $sale->service->name,
                            'create_date' => format_date($sale->created_at),
                            'payment_date' => format_date($sale->created_at),
                            'activation_date' => format_date($sale->activated_at),
                            'end_date' => format_date($sale->active_until),
                            'count' => 1,
                            'visits_left' => $sale->getRemainingVisitsAttribute(),
                            'price' => $sale->sale->transaction->amount * -1,
                            'amount_of_payments' => $sale->sale->transaction->amount * -1,
                            'payment_left' => 0,
                            'type_of_payment' => 'Наличные',
                            'manager' => '',
                        ];
                    })
                    ->toArray();

                $currentSheet->fromArray($_sales, null, 'A' . ($recordsTotal + 3), true);
                $recordsTotal += count($_sales);
                $this->line($recordsTotal);
            });

        $excelWriter = new Xlsx($template);
        $fileName = 'Импорт_услуги_клиентов_' . 'АТРИУМ' . '.xlsx';
        $path = "storage/excel/";
        \File::ensureDirectoryExists($path);
        $fullPath =  $path . $fileName;
        $excelWriter->save($fullPath);
    }

    private function write($sheet, $template, $name, $input)
    {
        $sheet->fromArray($input, null, 'A3', true);

        $excelWriter = new Xlsx($template);
        $fileName = 'Импорт_услуги_клиентов_' . $name . '.xlsx';
        $path = "storage/excel/";
        \File::ensureDirectoryExists($path);
        $fullPath =  $path . $fileName;
        $excelWriter->save($fullPath);
    }
}
