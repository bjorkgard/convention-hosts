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
            'name' => 'Section '.$this->faker->randomElement(['A', 'B', 'C', 'D', 'E', 'F', 'G', 'H']),
            'number_of_seats' => $this->faker->numberBetween(50, 500),
            'occupancy' => 0,
            'available_seats' => 0,
            'elder_friendly' => $this->faker->boolean(30),
            'handicap_friendly' => $this->faker->boolean(25),
            'information' => $this->faker->optional(0.4)->sentence(),
        ];
    }

    /**
     * Mark the section as elder-friendly.
     */
    public function elderFriendly(): static
    {
        return $this->state(fn (array $attributes) => [
            'elder_friendly' => true,
        ]);
    }

    /**
     * Mark the section as handicap-friendly.
     */
    public function handicapFriendly(): static
    {
        return $this->state(fn (array $attributes) => [
            'handicap_friendly' => true,
        ]);
    }

    /**
     * Set a specific occupancy percentage.
     */
    public function withOccupancy(int $occupancy): static
    {
        return $this->state(fn (array $attributes) => [
            'occupancy' => $occupancy,
            'available_seats' => (int) round(($attributes['number_of_seats'] ?? 100) * (1 - $occupancy / 100)),
        ]);
    }
}
