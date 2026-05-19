<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;

class DestroyUserClubs extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'user:club-clear';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Очищает поле club_id у пользователя, если у него больше 1 клуба';

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
            ->with('clubs')
            ->get()
            ->each(function (User $user) {
                if ($user->clubs->count() > 1) {
                    $user->update(['club_id' => null]);
                } else {
                    $user->update([
                        'club_id' => $user->clubs->first()->id,
                    ]);
                }
            });
    }
}
