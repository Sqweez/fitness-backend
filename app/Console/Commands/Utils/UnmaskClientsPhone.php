<?php

namespace App\Console\Commands\Utils;

use App\Models\Client;
use App\Models\User;
use Illuminate\Console\Command;

class UnmaskClientsPhone extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'clients:unmask';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Unmask clients phones';

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
        User::chunk(500, function ($clients) {
            $clients->each(function ($client) {
                $this->line('Updated id: ' . $client->id);
                $client->update(['phone' => unmask_phone($client->phone)]);
            });
        });
    }
}
