<?php

namespace Database\Factories;

use App\Models\Convention;
use App\Models\Floor;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Floor>
 */
class FloorFactory extends Factory
{
    protected $model = Floor::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'convention_id' => Convention::factory(),
            'name' => $this->faker->randomElement(['1st Floor', '2nd Floor', '3rd Floor', 'Ground Floor', 'Mezzanine']),
        ];
    }
}
