<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class UserFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition()
    {
        return [
            'name' => $this->faker->name(),
            //'login' => $this->faker->safeEmail(),
            'password' => Hash::make(123456),
            'phone' => $this->faker->phoneNumber(),
            'birth_date' => $this->faker->date(),
            'description' => $this->faker->text(),
            'club_id' => rand(1, 3)
        ];
    }

    /**
     * Indicate that the model's email address should be unverified.
     *
     * @return \Illuminate\Database\Eloquent\Factories\Factory
     */
    public function unverified()
    {

    }
}
