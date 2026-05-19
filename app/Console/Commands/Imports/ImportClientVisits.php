<?php

namespace App\Console\Commands\Imports;

use App\Models\Sale;
use App\Models\Service;
use App\Models\ServiceSale;
use App\Models\Session;
use App\Models\SessionService;
use App\Models\Transaction;
use Carbon\Carbon;
use Carbon\Exceptions\InvalidFormatException;
use Illuminate\Console\Command;

class ImportClientVisits extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'import:visits';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Импортирует посещения по услугам';

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
        /*Session::query()
            ->whereHas('session_service', function ($q) {
                return $q->whereNull('minutes');
            })
            ->delete();
        SessionService::whereNull('minutes')->delete();
        $this->line('Закончено удаление');*/
      /*  \DB::table('sessions')->truncate();
        \DB::table('session_services')->truncate();*/
        $hasData = true;
        $page = 1;
        while ($hasData) {
            $items = $this->getVisits($page);
            $hasData = count($items) > 0;
            collect($items)->each(function ($item) {
               # $this->line($item->nazvanie . " " . $item->id);
                $oldServiceTypeId = intval($item->class);
                $serviceTypeId = 1;
                if ($oldServiceTypeId === 0) {
                    $serviceTypeId = Service::TYPE_UNLIMITED;
                }
                if ($oldServiceTypeId === 1) {
                    $serviceTypeId = Service::TYPE_PROGRAM;
                }
                if ($oldServiceTypeId === 2) {
                    $serviceTypeId = Service::TYPE_SOLARIUM;
                }
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

                $sessionService = SessionService::create([
                    'service_sale_id' => $item->iduslugi,
                    'user_id' => $item->kto,
                    'session_id' => $session->id,
                    'minutes' => null,
                    'created_at' => $item->data,
                    'updated_at' => $item->data,
                ]);
            });
            $this->line('Импортировано ' . $page * 10000);
            $page++;
        }
        \DB::statement('SET FOREIGN_KEY_CHECKS=1;');
        return 0;
    }

    private function getVisits($page) {
        $client = new \GuzzleHttp\Client();
        $response = $client->get('http://top-star.kz/export/export_visits.php?page=' . $page);
        return json_decode($response->getBody());
    }
}
