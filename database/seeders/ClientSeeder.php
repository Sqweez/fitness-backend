<?php

namespace Database\Seeders;

use App\Models\Client;
use App\Models\Club;
use App\Models\User;
use Illuminate\Database\Seeder;

class ClientSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $clubId = Club::query()->whereKey(1)->value('id');
        $registrarId = User::query()->where('phone', '+79991112233')->value('id');

        Client::updateOrCreate(
            ['phone' => '+79990001122'],
            [
                'name' => 'Тестовый клиент',
                'gender' => 'M',
                'birth_date' => '1990-01-01',
                'club_id' => $clubId,
                'user_id' => $registrarId,
                'mobile_password' => '1234',
            ]
        );

        $currentCount = Client::query()->count();
        $targetCount = 100;

        if ($currentCount < $targetCount) {
            Client::factory()
                ->count($targetCount - $currentCount)
                ->create([
                    'club_id' => $clubId,
                    'user_id' => $registrarId,
                ]);
        }
    }
}
