<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Laravel\Sanctum\PersonalAccessToken;

class RevokeStaffTokens extends Command
{
    protected $signature = 'user:revoke-tokens';

    protected $description = 'Удаляет все токены сотрудников, чтобы разлогинить их';

    public function handle(): int
    {
        $deleted = PersonalAccessToken::query()
            ->where('tokenable_type', User::class)
            ->delete();

        $this->info("Удалено токенов: {$deleted}");

        return self::SUCCESS;
    }
}
