<?php

namespace App\Console\Commands\Imports;

use App\Models\ClientReplenishment;
use App\Models\WithDrawal;
use Illuminate\Console\Command;

class ImportReplenishments extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'import:replenishments';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Импорт пополнений баланса';

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
       /*  \DB::table('client_replenishments')->truncate();
        \DB::table('with_drawals')->truncate();*/
        $hasData = true;
        $page = 1;
        while ($hasData) {
            $items = $this->getData($page);
            $hasData = count($items) > 0;
            $this->line('Импортировано ' . $page * 50000);
            collect($items)->each(function ($item) {
                $this->line($item->com);
                $club = str_replace('club', '', $item->clubid);
                $clubId = $club ?: 2;
                if ($item->summa > 0) {
                    $r = ClientReplenishment::query()
                        ->create([
                            'id' => $item->id,
                            'client_id' => $item->idclienta,
                            'user_id' => $item->idprodazhnika,
                            'amount' => $item->summa,
                            'created_at' => $item->data,
                            'updated_at' => $item->data,
                            'club_id' => $clubId,
                            'description' => $item->com,
                            'payment_type' => $item->nal == 1 ? 1 : 2,
                        ]);

                    /*$r->transaction()->create([
                        'client_id' => $item->idclienta,
                        'user_id' => $item->idprodazhnika,
                        'amount' => $item->summa,
                        'created_at' => $item->data,
                        'updated_at' => $item->data,
                        'club_id' => $clubId,
                        'description' => 'Пополнение средств'
                    ]);*/
                } else {
                    WithDrawal::create([
                        'id' => $item->id,
                        'user_id' => $item->idprodazhnika,
                        'amount' => $item->summa * -1,
                        'created_at' => $item->data,
                        'updated_at' => $item->data,
                        'club_id' => $clubId,
                        'description' => $item->com,
                        'payment_type' => $item->nal == 1 ? 1 : 2,
                    ]);
                }
            });
            $page ++;
        }
        \DB::statement('SET FOREIGN_KEY_CHECKS=1;');
        return 0;
    }

    public function getData($page) {
        $client = new \GuzzleHttp\Client();
        $response = $client->get('http://top-star.kz/export/export_replenishments.php?page=' . $page);
        return json_decode($response->getBody());
    }
}
