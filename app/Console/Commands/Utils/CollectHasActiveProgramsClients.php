<?php

namespace App\Console\Commands\Utils;

use App\Models\Client;
use App\Models\ServiceSale;
use Carbon\Carbon;
use Illuminate\Console\Command;

class CollectHasActiveProgramsClients extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'clients:active-programs';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Собирает всех клиентов и проверяет активные ли у них программы';

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
            ->update(['has_active_programs' => false]);

        $clients = Client::query()
            ->select(['id'])
            ->whereHas('programs', function ($q) {
                return $q
                    ->whereHasMorph('salable', [ServiceSale::class], function ($q) {
                        return $q->whereDate('active_until', '>=', today());
                    });
            })
            ->with('programs.salable')
            ->get();

        $clients = $clients->filter(function (Client $client) {
            $activePrograms = $client->programs->filter(function ($program) {
                return ($program->salable->active_until !== null ||
                        Carbon::parse($program->salable->active_until)->gte(today())) &&
                    !in_array($program->salable->service_id, [176, 148]);
            });
            return $activePrograms->count() > 0;
        })->values()->map(function (Client $client) {
            return $client->id;
        });

        Client::query()
            ->whereIn('id', $clients)
            ->update(['has_active_programs' => true]);

        return 0;
    }
}
