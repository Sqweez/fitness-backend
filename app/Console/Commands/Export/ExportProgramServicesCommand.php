<?php

namespace App\Console\Commands\Export;

use App\Models\Service;
use Illuminate\Console\Command;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

class ExportProgramServicesCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'export:programs';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Export programs';

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
        $template = IOFactory::load('excel/Импорт_шаблоны_услуг.xlsx');
        $currentSheet = $template->getActiveSheet();
        $servicesWoman = Service::query()
            ->where('name', 'not like', '%test%')
            ->where('name', 'not like', '%тест%')
            ->where('name', '!=', '')
            ->whereNotIn('id', [583, 1361, 1362, 1163, 1365, 1864, 1865, 204])
            ->where('price', '>', 5)
            ->whereServiceTypeId(3)
            ->withTrashed()
            ->whereClubId(1)
            ->get()
            ->map(function (Service $service) {
                return [
                    'name' => $service->name,
                    'price' => $service->price,
                    'duration' => $service->validity_days,
                    'visits' => $service->entries_count,
                    'do_enter' => 1,
                    'first_visit_activation' => 1,
                    'archive' => $service->is_active ? 0 : 1,
                ];
            })
            ->toArray();

        $servicesAtrium = Service::query()
            ->where('name', 'not like', '%test%')
            ->where('name', 'not like', '%тест%')
            ->where('name', '!=', '')
            ->whereNotIn('id', [583, 1361, 1362, 1163, 1365, 1864, 1865, 204])
            ->where('price', '>', 5)
            ->whereServiceTypeId(3)
            ->withTrashed()
            ->whereClubId([2, 3])
            ->where('is_active', true)
            ->get()
            ->map(function (Service $service) {
                return [
                    'name' => $service->name,
                    'price' => $service->price,
                    'duration' => $service->validity_days,
                    'visits' => $service->entries_count,
                    'do_enter' => 1,
                    'first_visit_activation' => 1,
                    'archive' => $service->is_active ? 0 : 1,
                ];
            })
            ->toArray();

        $this->write($currentSheet, $template, 'АТРИУМ', $servicesAtrium);
        $this->write($currentSheet, $template, 'СТУДИЯ', $servicesWoman);
    }

    private function write($sheet, $template, $name, $input)
    {
        $sheet->fromArray($input, null, 'A3', true);

        $excelWriter = new Xlsx($template);
        $fileName = 'Импорт_шаблоны_услуг_' . $name . '.xlsx';
        $path = "storage/excel/";
        \File::ensureDirectoryExists($path);
        $fullPath =  $path . $fileName;
        $excelWriter->save($fullPath);
    }
}
