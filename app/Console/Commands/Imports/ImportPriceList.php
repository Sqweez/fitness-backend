<?php

namespace App\Console\Commands\Imports;

use App\Models\Service;
use Illuminate\Console\Command;

class ImportPriceList extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'import:price';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Импортирует прайс-лист из старой программы';

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
        \DB::table('services')->truncate();;
        $services = $this->getServices();
        collect($services)->each(function ($service) {
            $serviceTypeId = 1;
            $oldServiceTypeId = intval($service->class);
            if ($oldServiceTypeId === 0) {
                $serviceTypeId = Service::TYPE_UNLIMITED;
            }
            if ($oldServiceTypeId === 1) {
                $serviceTypeId = Service::TYPE_PROGRAM;
            }
            if ($oldServiceTypeId === 2) {
                $serviceTypeId = Service::TYPE_SOLARIUM;
            }
            $this->line($service->nazvanie);
            Service::query()
                ->updateOrCreate(
                    [
                        'id' => $service->id
                    ],
                    [
                        'name' => $service->nazvanie,
                        'price' => $service->cena,
                        'description' => $service->opis,
                        'validity_days' => $serviceTypeId === Service::TYPE_SOLARIUM ? 999 : $service->srok,
                        'validity_minutes' => $serviceTypeId === Service::TYPE_SOLARIUM ? $service->srok : null,
                        'club_id' => str_replace('club', '', $service->clubid),
                        'service_type_id' => $serviceTypeId,
                        'entries_count' => $serviceTypeId === Service::TYPE_PROGRAM ? $service->kolvo : null,
                        'unlimited_price' => intval($service->cena) === 0 ? $service->cena : $service->price_bezlim_skidka,
                        'prolongation_price' => 0,
                        'restore_price' => 1500,
                        'is_active' => $service->status == 1,
                    ]
                );
        });
    }

    private function getServices() {
        $client = new \GuzzleHttp\Client();
        $response = $client->get('http://top-star.kz/export/export_price.php');
        return json_decode($response->getBody());
    }
}
