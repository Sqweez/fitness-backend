<?php

namespace Database\Factories;

use App\Models\Club;
use App\Models\Service;

class ServiceFactory extends Factory
{
    protected $model = Service::class;

    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition()
    {
        $type = $this->faker->randomElement([
            Service::TYPE_UNLIMITED,
            Service::TYPE_SOLARIUM,
            Service::TYPE_PROGRAM,
        ]);
        $price = $this->faker->numberBetween(500, 5000);
        $validityDays = in_array($type, [Service::TYPE_UNLIMITED, Service::TYPE_PROGRAM], true)
            ? $this->faker->numberBetween(30, 365)
            : 15000;

        return [
            'name' => $this->faker->unique()->words(3, true),
            'description' => $this->faker->optional()->sentence(),
            'price' => $price,
            'prolongation_price' => $this->faker->numberBetween(0, 1000),
            'unlimited_price' => $type === Service::TYPE_SOLARIUM
                ? $price
                : $this->faker->numberBetween($price, $price * 3),
            'validity_days' => $validityDays,
            'validity_minutes' => $type === Service::TYPE_SOLARIUM
                ? $this->faker->numberBetween(30, 300)
                : null,
            'entries_count' => $type === Service::TYPE_PROGRAM
                ? $this->faker->numberBetween(5, 30)
                : null,
            'club_id' => Club::query()->inRandomOrder()->value('id'),
            'service_type_id' => $type,
            'restore_price' => $this->faker->numberBetween(0, 1000),
            'is_active' => true,
        ];
    }
}
