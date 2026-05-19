<?php

namespace App\Console\Commands;

use App\Http\Services\ClientService;
use App\Models\Client;
use Illuminate\Console\Command;

class FinishClientSessions extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'sessions:finish';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Завершает клиентские посещения и открепляет ключи в 00:00';

    private ClientService $service;

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct(ClientService $service)
    {
        parent::__construct();
        $this->service = $service;
    }

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        Client::query()
            ->has('active_session')
            ->get()
            ->each(function ($client) {
                $this->service->finish($client);
            });
    }
}
