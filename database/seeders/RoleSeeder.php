<?php

namespace Database\Seeders;

use App\Models\Role;
use Illuminate\Database\Seeder;

class RoleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $roles = [
            'Главный администратор',
            'Администратор',
            'Отдел продаж',
            'Бармен',
            'Модератор',
            'Тренер',
            'Бухгалтер',
            'Старший бармен'
        ];
        foreach ($roles as $key => $role) {
            Role::updateOrCreate(['id' => $key + 1], ['name' => $role]);
        }
    }
}
