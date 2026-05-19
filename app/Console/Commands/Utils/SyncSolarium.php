<?php

namespace App\Console\Commands\Utils;

use App\Models\Client;
use App\Models\Sale;
use App\Models\ServiceSale;
use Illuminate\Console\Command;

class SyncSolarium extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'sync:solarium';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

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
        $cl = Client::where('cached_solarium_total', '>', 0)->get();
        $cl->each(function (Client $client) {

            $s = ServiceSale::create([
                'service_id' => __hardcoded(1204),
                'entries_count' => null,
                'minutes_remaining' => 0,
                'active_until' => now(),
                'activated_at' => now(),
                'self_name' => __hardcoded('Солярий резерв')
            ]);

            $sale = Sale::create([
                'client_id' => $client->id,
                'club_id' => $client->club_id,
                'salable_type' => 'App\\Models\\ServiceSale',
                'salable_id' => $s->id,
                'user_id' => __hardcoded(1)
            ]);
        });
    }
}
