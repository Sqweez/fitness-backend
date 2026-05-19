<?php

namespace App\Console\Commands\Utils;

use Illuminate\Console\Command;

class RegenerateTransactions extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'transactions:restore';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Ресторит утерянные транзакции';

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
        return 0;
    }
}
