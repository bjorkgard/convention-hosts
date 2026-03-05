<?php

namespace Database\Factories;

use App\Models\Convention;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Convention>
 */
class ConventionFactory extends Factory
{
    protected $model = Convention::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $startDate = $this->faker->dateTimeBetween('now', '+1 year');
        $endDate = $this->faker->dateTimeBetween($startDate, '+2 years');

        return [
            'name' => $this->faker->company().' Convention',
            'city' => $this->faker->city(),
            'country' => $this->faker->country(),
            'address' => $this->faker->address(),
            'start_date' => $startDate,
            'end_date' => $endDate,
            'other_info' => $this->faker->optional()->paragraph(),
        ];
    }
}
