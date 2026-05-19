<?php

namespace App\Console\Commands\Export;

use Illuminate\Console\Command;

class ExportCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'export:all';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Export All';

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
        ini_set('memory_limit', '2000M');
        $this->info('Export clients');
        $this->callCommand(ExportClientsCommand::class);
        $this->info('Export products');
        $this->callCommand(ExportProductsCommand::class);
        $this->info('Export unlimited');
        $this->callCommand(ExportUnlimitedServicesCommand::class);
        $this->info('Export services');
        $this->callCommand(ExportProgramServicesCommand::class);
        $this->info('Export client products');
        $this->callCommand(ExportClientProducts::class);
        $this->info('Export client programs');
        $this->callCommand(ExportClientProgramsCommand::class);
        $this->info('Export client unlimited');
        $this->callCommand(ExportClientUnlimitedCommand::class);
        $this->info('Export client visits');
        $this->callCommand(ExportClientVisits::class);
        $this->info('Export client visits unlimited');
        $this->callCommand(ExportClientVisitsUnlimited::class);
    }

    protected function callCommand($commandClass)
    {
        $command = app($commandClass);
        $command->setLaravel($this->laravel);

        $input = new \Symfony\Component\Console\Input\ArrayInput([]);
        $output = new \Symfony\Component\Console\Output\BufferedOutput;

        $command->run($input, $output);
    }
}
