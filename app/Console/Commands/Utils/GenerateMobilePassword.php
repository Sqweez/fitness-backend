<?php

namespace App\Console\Commands\Utils;

use App\Models\Client;
use Illuminate\Console\Command;

class GenerateMobilePassword extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'mobile:generate';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Генерирует пароль для мобильного приложения';

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
            ->each(function (Client $client) {
                $code = mt_rand(100000, 999999);
                $client->update([
                    'mobile_password' => $code
                ]);
            });
    }
}
