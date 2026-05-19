<?php

namespace App\Console\Commands\Export;

use App\Models\Product;
use App\Models\Service;
use Illuminate\Console\Command;
use PhpOffice\PhpSpreadsheet\Calculation\TextData\Format;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Style\NumberFormat;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

class ExportProductsCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'export:products';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Export products';

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
    public function handle(): int
    {
        $template = IOFactory::load('excel/Импорт_шаблоны_товаров.xlsx');
        $currentSheet = $template->getActiveSheet();
        $productsW = Product::query()
            ->withTrashed()
            ->with(['batches' => function ($q) {
                return $q->whereStoreId(1);
            }])
            ->get()
            ->map(function (Product $product) {
                return [
                    'name' => trim(sprintf('%s %s', $product->name, $product->attribute)),
                    'count' => $product->batches->reduce(function ($a, $c) {
                        return $a + $c['quantity'];
                    }, 0),
                    'price' => $product->price,
                    'code' => $product->barcode,
                    'archive' => is_null($product->deleted_at) ? 0 : 1,
                ];
            })
            ->filter(function ($item) {
                return $item['count'] > 0;
            })
            ->toArray();

        $productsA = Product::query()
            ->withTrashed()
            ->with(['batches' => function ($q) {
                return $q->whereIn('store_id', [2, 3]);
            }])
            ->get()
            ->map(function (Product $product) {
                return [
                    'name' => trim(sprintf('%s %s', $product->name, $product->attribute)),
                    'count' => $product->batches->sum('quantity'),
                    'price' => $product->price,
                    'code' => $product->barcode,
                    'archive' => is_null($product->deleted_at) ? 0 : 1,
                ];
            })
            ->filter(function ($item) {
                return $item['count'] > 0;
            })
            ->toArray();

        $this->write($currentSheet, $template, 'АТРИУМ', $productsA);
        $this->write($currentSheet, $template, 'СТУДИЯ', $productsW);

        return self::SUCCESS;
    }

    private function write(Worksheet $sheet, $template, $name, $input)
    {
        $sheet->fromArray($input, null, 'A3', true);
        $sheet->getStyle('D3:D' . count($input))->getNumberFormat()->setFormatCode('0');
        $excelWriter = new Xlsx($template);
        $fileName = 'Импорт_шаблоны_товаров_' . $name . '.xlsx';
        $path = "storage/excel/";
        \File::ensureDirectoryExists($path);
        $fullPath =  $path . $fileName;
        $excelWriter->save($fullPath);
    }
}
