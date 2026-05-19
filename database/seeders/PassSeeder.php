<?php

namespace Database\Seeders;

use App\Models\Pass;
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
        Pass::factory()
            ->count(10)
            ->create();
    }
}
