<?php

namespace App\Console\Commands\Imports;

use App\Models\ProductSale;
use App\Models\Sale;
use Illuminate\Console\Command;

class ImportShopHistory extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'import:shop-history';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Импортирует историю магазина';

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
        $hasItems = true;
        $page = 1;
        while ($hasItems) {
            $items = $this->getItems($page);
            $hasItems = count($items) > 0;
            $page++;
            collect($items)->each(function ($item) {
                if ($item->product_id) {
                    $productSale = ProductSale::query()
                        ->create([
                            'product_id' => $item->product_id,
                            'product_batch_id' => null,
                            'created_at' => $item->data,
                            'updated_at' => $item->data,
                            'purchase_price' => 0
                        ]);

                    $club_id = str_replace('club', '', $item->clubid);
                    if (!$club_id) {
                        $club_id = 2;
                    }

                    $sale = Sale::query()
                        ->create([
                            'client_id' => $item->client_id,
                            'club_id' => 2,
                            'user_id' => $item->prod_id,
                            'salable_type' => 'App\\Models\\ProductSale',
                            'salable_id' => $productSale->id,
                            'created_at' => $item->data,
                            'updated_at' => $item->data,
                        ]);

                    $sale->transaction()->create([
                        'user_id' => $item->prod_id,
                        'client_id' => $item->client_id,
                        'amount' => intval($item->summa) * -1,
                        'club_id' => $club_id,
                        'description' => 'Списание средств за покупку в баре',
                        'updated_at' => $item->data,
                        'created_at' => $item->data,
                    ]);
                }

            });
            $this->line('Экспортировано: '  . ($page - 1) * 10000 );
        }
    }

    public function getItems($page) {
        $client = new \GuzzleHttp\Client();
        $response = $client->get('http://top-star.kz/export/export_shop_history.php?page=' . $page);
        $this->line('Закончено получение');
        return json_decode($response->getBody());
    }
}
