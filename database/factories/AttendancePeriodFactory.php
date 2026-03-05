<?php

namespace Database\Factories;

use App\Models\AttendancePeriod;
use App\Models\Convention;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\AttendancePeriod>
 */
class AttendancePeriodFactory extends Factory
{
    protected $model = AttendancePeriod::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'convention_id' => Convention::factory(),
            'date' => $this->faker->dateTimeBetween('now', '+30 days'),
            'period' => $this->faker->randomElement(['morning', 'afternoon']),
            'locked' => false,
        ];
    }

    /**
     * Indicate that the period is locked.
     */
    public function locked(): static
    {
        return $this->state(fn (array $attributes) => [
            'locked' => true,
        ]);
    }
}
