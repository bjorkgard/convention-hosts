<?php

namespace Database\Factories;

use App\Models\Floor;
use App\Models\Section;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Section>
 */
class SectionFactory extends Factory
{
    protected $model = Section::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'floor_id' => Floor::factory(),
            'name' => $this->faker->randomElement(['Section A', 'Section B', 'Section C', 'Section D']),
            'number_of_seats' => $this->faker->numberBetween(50, 300),
            'occupancy' => 0,
            'available_seats' => 0,
            'elder_friendly' => $this->faker->boolean(),
            'handicap_friendly' => $this->faker->boolean(),
            'information' => $this->faker->optional()->sentence(),
        ];
    }
}
