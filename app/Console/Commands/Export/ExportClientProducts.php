<?php

namespace App\Console\Commands\Export;

use App\Models\ProductSale;
use App\Models\Client;
use App\Models\SessionService;
use Illuminate\Console\Command;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use ZipArchive;

class ExportClientProducts extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'export:client-products';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Export client products';

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
        ini_set('max_execution_time', '1000000');
        $sourceFolder = base_path('storage/excel');
        $zipFileName = 'photo_' . time() . '.zip';
        $zipFilePath = public_path($zipFileName);
        $zip = new ZipArchive;

        if ($zip->open($zipFilePath, ZipArchive::CREATE | ZipArchive::OVERWRITE) === true) {
            Client::query()
                ->has('avatar')
                ->with('avatar')
                ->withTrashed()
                ->orderBy('id')
                ->cursor()
                ->each(function ($client) use ($zip) {
                    $this->line($client->id);
                    $avatar = $client->avatar->first();
                    if ($avatar) {
                        $filePath = $avatar->getPath();
                        $newFileName = "{$client->id}." . pathinfo($filePath, PATHINFO_EXTENSION);
                        $zip->addFile($filePath, $newFileName);
                    }
                });
            $zip->close();

            // Возвращаем архив как ответ на скачивание
            //return response()->download($zipFilePath)->deleteFileAfterSend(true);
        } else {
           // return response()->json(['error' => 'Failed to create archive'], 500);
        }
        return 0;
        $this->do([1], 'СТУДИЯ');
        $this->do([2, 3], 'АТРИУМ');
    }

    private function do($clubIds, $name)
    {
        return 0;
        $template = IOFactory::load('excel/Импорт_товаров_клиентов.xlsx');
        $currentSheet = $template->getActiveSheet();
        $recordsTotal = 0;
        ProductSale::query()
            ->whereHas('sale', function ($query) use ($clubIds) {
                return $query->whereIn('club_id', $clubIds);
            })
            ->with('sale.client')
            ->with('sale.user')
            ->with('sale.transaction')
            ->with('product')
            ->chunk(1000, function ($services) use (&$currentSheet, &$recordsTotal) {
                $_services = $services
                    ->filter(function (ProductSale $service) {
                        return isset($service->sale->client) && isset($service->sale->transaction) && isset($service->product);
                    })
                    ->map(function (ProductSale $service) {
                        return [
                            'id' => '',
                            'phone' => $service->sale->client->phone,
                            'client_fio' => $service->sale->client->name,
                            'product_name' => trim(sprintf('%s %s', $service->product->name, $service->product->attribute)),
                            'create_date' => format_date($service->created_at),
                            'count' => 1,
                            'price' => $service->sale->transaction->amount * -1,
                            'amount_of_payment' => $service->sale->transaction->amount * -1,
                            'type_of_payment' => 0,
                            'type_of_payment1' => 'Наличные',
                            'manager' => $service->sale->user->id === - 1 ? null : $service->sale->user->name,
                        ];
                    })
                    ->toArray();

                $this->line($recordsTotal);
                $currentSheet->fromArray($_services, null, 'A' . ($recordsTotal + 3), true);
                $recordsTotal += count($_services);
            });

        $excelWriter = new Xlsx($template);
        $fileName = 'Импорт_товаров_клиентов_' . $name . '.xlsx';
        $path = "storage/excel/";
        \File::ensureDirectoryExists($path);
        $fullPath =  $path . $fileName;
        $excelWriter->save($fullPath);
    }
}
