<?php

namespace Database\Factories;

use App\Models\Convention;
use App\Models\User;
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
        $startDate = $this->faker->dateTimeBetween('+1 week', '+6 months');
        $endDate = $this->faker->dateTimeBetween($startDate, (clone $startDate)->modify('+2 weeks'));

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

    /**
     * Create the convention with an owner user attached.
     */
    public function withOwner(?User $owner = null): static
    {
        return $this->afterCreating(function (Convention $convention) use ($owner) {
            $user = $owner ?? User::factory()->create();

            $convention->users()->attach($user->id);

            \Illuminate\Support\Facades\DB::table('convention_user_roles')->insert([
                [
                    'convention_id' => $convention->id,
                    'user_id' => $user->id,
                    'role' => 'Owner',
                    'created_at' => now(),
                ],
                [
                    'convention_id' => $convention->id,
                    'user_id' => $user->id,
                    'role' => 'ConventionUser',
                    'created_at' => now(),
                ],
            ]);
        });
    }
}
