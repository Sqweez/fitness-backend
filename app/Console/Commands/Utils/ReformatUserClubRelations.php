<?php

namespace App\Console\Commands\Utils;

use App\Models\User;
use Illuminate\Console\Command;

class ReformatUserClubRelations extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'user:clubs';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Переформатирование отношения клуб/пользователь';

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
        User::all()
            ->each(function (User $user) {
                $club_id = $user->club_id;
                $this->info('Пользователь: ' . $user->name);
                $user->update([
                    'club_id' => null
                ]);
                $user->clubs()->attach($club_id);
            });
    }
}
