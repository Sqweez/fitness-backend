<?php

namespace App\Console\Commands\Utils;

use App\Models\User;
use Illuminate\Console\Command;

class DestroyInactiveCard extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'pass:destroy';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Удаляет карты неактивных сотрудников';

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
        User::query()
            ->where('is_active', false)
            ->has('pass')
            ->with('pass')
            ->get()
            ->each(function (User $user) {
                $this->line($user->name);
                $user->pass()->delete();
            });
    }
}
