<?php

namespace App\Console\Commands\Utils;

use App\Models\Client;
use App\Models\ServiceSale;
use Illuminate\Console\Command;

class CollectHasActiveProgramsClients extends Command
{
    private const EXCLUDED_SERVICE_IDS = [176, 148];

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

        Client::query()
            ->whereHas('programs', function ($programs) {
                $programs->whereHasMorph('salable', [ServiceSale::class], function ($serviceSale) {
                    $serviceSale->whereDate('active_until', '>=', today());
                });
            })
            ->whereHas('programs', function ($programs) {
                $programs->whereHasMorph('salable', [ServiceSale::class], function ($serviceSale) {
                    $serviceSale->whereNotIn('service_id', self::EXCLUDED_SERVICE_IDS);
                });
            })
            ->update(['has_active_programs' => true]);

        return 0;
    }
}
