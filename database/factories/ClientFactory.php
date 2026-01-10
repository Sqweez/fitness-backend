<?php

namespace Database\Factories;

use App\Models\Club;
use App\Models\Client;
use App\Models\User;

class ClientFactory extends Factory
{
    protected $model = Client::class;

    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition()
    {
        return [
            'name' => $this->faker->name(),
            'phone' => $this->faker->unique()->numerify('+7##########'),
            'gender' => $this->faker->randomElement(['M', 'F']),
            'description' => $this->faker->optional()->sentence(),
            'birth_date' => $this->faker->dateTimeBetween('-60 years', '-18 years')->format('Y-m-d'),
            'club_id' => Club::query()->inRandomOrder()->value('id'),
            'user_id' => User::query()->inRandomOrder()->value('id'),
            'mobile_password' => null,
        ];
    }
}
