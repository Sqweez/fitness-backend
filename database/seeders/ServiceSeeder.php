<?php

namespace Database\Seeders;

use App\Models\Club;
use App\Models\Service;
use Illuminate\Database\Seeder;

class ServiceSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $clubId = Club::query()->whereKey(1)->value('id');
        $currentCount = Service::query()->count();
        $targetCount = 15;

        if ($currentCount >= $targetCount) {
            return;
        }

        Service::factory()
            ->count($targetCount - $currentCount)
            ->create([
                'club_id' => $clubId,
            ]);
    }
}
