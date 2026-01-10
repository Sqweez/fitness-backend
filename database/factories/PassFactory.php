<?php

namespace Database\Factories;

class PassFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition()
    {
        return [
            'code' => rand(10000000, 99999999)
        ];
    }
}
