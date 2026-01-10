<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     *
     * @return void
     */
    public function run()
    {
        $this->call([
            RoleSeeder::class,
            ClubSeeder::class,
            ServiceSeeder::class,
            UserSeeder::class,
            ClientSeeder::class,
            PassSeeder::class,
        ]);
    }
}
