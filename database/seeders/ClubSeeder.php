<?php

namespace Database\Seeders;

use App\Models\Club;
use Illuminate\Database\Seeder;

class ClubSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        Club::updateOrCreate(['id' => 1], ['name' => 'Top Star Женская Студия']);
        Club::updateOrCreate(['id' => 2], ['name' => 'Top Star Атриум']);
        Club::updateOrCreate(['id' => 3], ['name' => 'Top Star Kids']);
    }
}
