<?php

namespace Database\Seeders;

use App\Models\Club;
use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $clubId = Club::query()->whereKey(1)->value('id');

        $user = User::updateOrCreate(
            ['phone' => '+79991112233'],
            [
                'name' => 'Администратор',
                'password' => Hash::make('password'),
                'club_id' => $clubId,
                'is_active' => true,
            ]
        );

        $user->roles()->syncWithoutDetaching([Role::ROLE_BOSS]);
        if ($clubId) {
            $user->clubs()->syncWithoutDetaching([$clubId]);
        }
    }
}
