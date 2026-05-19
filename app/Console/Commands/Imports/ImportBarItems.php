<?php

namespace App\Console\Commands\Imports;

use App\Models\Product;
use App\Models\ProductBatch;
use Illuminate\Console\Command;

class ImportBarItems extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'import:bar';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Импортируем барное меню';

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
/*        \DB::table('products')->where('product_type_id', 2)->delete();*/
        $items = $this->getItems();
        collect($items)->each(function ($item) {
            $this->line($item->name);
            $product = Product::query()
                ->create([
                    'id' => 10000 + $item->id,
                    'name' => $item->name,
                    'barcode' => $item->sku,
                    'price' => $item->price,
                    'product_type_id' => __hardcoded(2),
                    'product_category_id' => __hardcoded(2),
                    'product_group_id' => 10000 + $item->id,
                ]);

            if ($item->kolvo > 0) {
                ProductBatch::query()
                    ->create([
                        'initial_quantity' => $item->kolvo,
                        'quantity' => $item->kolvo,
                        'product_id' => 10000 + $item->id,
                        'store_id' => 2,
                        'purchase_price' => 0
                    ]);
            }
        });
    }

    public function getItems() {
        $client = new \GuzzleHttp\Client();
        $response = $client->get('http://top-star.kz/export/export_bar.php');
        return json_decode($response->getBody());
    }
}
