<?php

namespace App\Console\Commands\Utils;

use App\Models\Client;
use Illuminate\Console\Command;

class CacheClientsPass extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'clients-pass:cache';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Кэширует клиентские пропуска';

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
        Client::query()
            ->has('pass')
            ->with('pass')
            ->get()
            ->each(function (Client $client) {
                $this->line($client->name);
                $client->update(['cached_pass' => $client->pass->code]);
            });
    }
}
