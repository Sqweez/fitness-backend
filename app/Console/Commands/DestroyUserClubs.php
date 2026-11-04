<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

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
    public function handle(): int
    {
        User::query()
            ->has('clubs', '>', 1)
            ->whereNotNull('club_id')
            ->update(['club_id' => null]);

        $singleClubId = '(select clubs.id from clubs join club_user on club_user.club_id = clubs.id where club_user.user_id = users.id limit 1)';

        User::query()
            ->has('clubs', '=', 1)
            ->whereRaw("not (club_id <=> {$singleClubId})")
            ->update(['club_id' => DB::raw($singleClubId)]);

        return self::SUCCESS;
    }
}
