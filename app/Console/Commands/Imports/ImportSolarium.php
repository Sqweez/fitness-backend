<?php

namespace App\Console\Commands\Imports;

use App\Models\Service;
use App\Models\ServiceSale;
use App\Models\Session;
use App\Models\SessionService;
use Illuminate\Console\Command;

class ImportSolarium extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'import:solarium';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Импортирует весь солярий';

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
        \DB::statement('SET FOREIGN_KEY_CHECKS=0;');
        /*  \DB::table('sessions')->truncate();
          \DB::table('session_services')->truncate();*/
      /*  Session::query()
            ->whereHas('session_service', function ($q) {
                return $q->whereNotNull('minutes');
            })
            ->delete();
        SessionService::whereNotNull('minutes')->delete();
        $this->line('Закончено удаление');*/
        $items = $this->getVisits();

        $this->line(count($items));
        collect($items)->each(function ($item) {
            # $this->line($item->nazvanie . " " . $item->id);
            $oldServiceTypeId = intval($item->class);
            $serviceTypeId = Service::TYPE_SOLARIUM;
            $session = Session::create([
                'client_id' => $item->idclient,
                'start_user_id' => $item->kto,
                'finish_user_id' => $item->kto,
                'club_id' => str_replace('club', '', $item->club),
                'finished_at' => $item->data,
                'created_at' => $item->data,
                'updated_at' => $item->data,
                'trinket_id' => null,
            ]);

            $serviceSale = ServiceSale::query()
                ->whereHas('sale', function ($query) use ($session) {
                    $query->where('client_id', $session->client_id);
                })
                ->whereHas('service', function ($query) {
                    return $query->where('service_type_id', Service::TYPE_SOLARIUM);
                })
                ->first();

            $serviceSaleId = $serviceSale ? $serviceSale->id : $item->iduslugi;

            SessionService::create([
                'service_sale_id' => $serviceSaleId,
                'user_id' => $item->kto,
                'session_id' => $session->id,
                'minutes' => $item->kolvo > 0 ? $item->kolvo : $item->kolvo * -1,
                'created_at' => $item->data,
                'updated_at' => $item->data,
            ]);
        });
        \DB::statement('SET FOREIGN_KEY_CHECKS=1;');
        return 0;
    }

    private function getVisits() {
        $client = new \GuzzleHttp\Client();
        $response = $client->get('http://top-star.kz/export/export_solarium.php');
        return json_decode($response->getBody());
    }
}
