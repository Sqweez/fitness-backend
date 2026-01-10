<?php

namespace Database\Seeders;

use App\Models\Client;
use App\Models\Pass;
use App\Models\User;
use Illuminate\Database\Seeder;

class PassSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $user = User::query()->where('phone', '+79991112233')->first();
        if ($user) {
            Pass::updateOrCreate(
                ['code' => '10000001'],
                [
                    'passable_id' => $user->id,
                    'passable_type' => User::class,
                ]
            );
        }

        $client = Client::query()->where('phone', '+79990001122')->first();
        if ($client) {
            Pass::updateOrCreate(
                ['code' => '20000002'],
                [
                    'passable_id' => $client->id,
                    'passable_type' => Client::class,
                ]
            );
        }
    }
}
