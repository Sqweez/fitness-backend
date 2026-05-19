<?php

namespace App\Console\Commands\Imports;

use App\Models\Product;
use App\Models\ProductBatch;
use Illuminate\Console\Command;

class ImportShopItems extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'import:shop';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Импортирует магазин';

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
        \DB::table('products')->where('product_type_id', 1)->delete();
        $items = $this->getItems();
        collect($items)->each(function ($item) {
            $this->line($item->name);
            $product = Product::query()
                ->create([
                    'id' => $item->id,
                    'name' => $item->name,
                    'barcode' => $item->sku,
                    'price' => $item->price,
                    'product_type_id' => __hardcoded(1),
                    'product_category_id' => __hardcoded(1),
                    'product_group_id' => $item->id,
                ]);

            if ($item->kolvo != 0) {
                ProductBatch::query()
                    ->create([
                        'initial_quantity' => $item->kolvo,
                        'quantity' => $item->kolvo,
                        'product_id' => $item->id,
                        'store_id' => str_replace('club', '', $item->club),
                        'purchase_price' => 0
                    ]);
            }
        });
    }

    public function getItems() {
        $client = new \GuzzleHttp\Client();
        $response = $client->get('http://top-star.kz/export/export_shop.php');
        return json_decode($response->getBody());
    }
}
