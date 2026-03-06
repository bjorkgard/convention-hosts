<?php

use App\Models\User;

use function Pest\Laravel\actingAs;

/**
 * Property 1: Convention Creation Requires All Mandatory Fields
 *
 * For any convention creation attempt, if any of the required fields
 * (name, city, country, start_date, end_date) are missing, the system
 * should reject the creation with validation errors.
 *
 * **Validates: Requirements 1.1**
 */
beforeEach(function () {
    $this->user = User::factory()->create();
});

/**
 * Generate valid convention data with random faker values.
 */
function validConventionData(): array
{
    $startDate = now()->addDays(fake()->numberBetween(1, 60));
    $endDate = (clone $startDate)->addDays(fake()->numberBetween(1, 14));

    return [
        'name' => fake()->company().' Convention',
        'city' => fake()->city(),
        'country' => fake()->country(),
        'start_date' => $startDate->format('Y-m-d'),
        'end_date' => $endDate->format('Y-m-d'),
        'address' => fake()->optional()->address(),
        'other_info' => fake()->optional()->paragraph(),
    ];
}

it('rejects convention creation when each mandatory field is missing', function () {
    actingAs($this->user);

    $requiredFields = ['name', 'city', 'country', 'start_date', 'end_date'];

    for ($iteration = 0; $iteration < 3; $iteration++) {
        foreach ($requiredFields as $missingField) {
            $data = validConventionData();
            unset($data[$missingField]);

            $response = $this->post(route('conventions.store'), $data);

            $response->assertSessionHasErrors($missingField,
                "Iteration {$iteration}: Missing '{$missingField}' should produce a validation error"
            );
        }
    }

    // No conventions should have been created
    $this->assertDatabaseCount('conventions', 0);
})->group('property', 'convention-creation');

it('rejects convention creation when mandatory fields are empty strings', function () {
    actingAs($this->user);

    $requiredFields = ['name', 'city', 'country', 'start_date', 'end_date'];

    for ($iteration = 0; $iteration < 3; $iteration++) {
        foreach ($requiredFields as $emptyField) {
            $data = validConventionData();
            $data[$emptyField] = '';

            $response = $this->post(route('conventions.store'), $data);

            $response->assertSessionHasErrors($emptyField,
                "Iteration {$iteration}: Empty '{$emptyField}' should produce a validation error"
            );
        }
    }

    $this->assertDatabaseCount('conventions', 0);
})->group('property', 'convention-creation');

it('rejects convention creation when mandatory fields are null', function () {
    actingAs($this->user);

    $requiredFields = ['name', 'city', 'country', 'start_date', 'end_date'];

    for ($iteration = 0; $iteration < 3; $iteration++) {
        foreach ($requiredFields as $nullField) {
            $data = validConventionData();
            $data[$nullField] = null;

            $response = $this->post(route('conventions.store'), $data);

            $response->assertSessionHasErrors($nullField,
                "Iteration {$iteration}: Null '{$nullField}' should produce a validation error"
            );
        }
    }

    $this->assertDatabaseCount('conventions', 0);
})->group('property', 'convention-creation');

/**
 * Property 2: Optional Fields Are Accepted
 *
 * For any convention, creating it with or without optional fields
 * (address, other_info) should succeed as long as all required fields
 * are present.
 *
 * **Validates: Requirements 1.2**
 */
it('creates convention successfully with all optional fields provided', function () {
    actingAs($this->user);

    for ($iteration = 0; $iteration < 3; $iteration++) {
        $startDate = now()->addDays(fake()->numberBetween(1, 60));
        $endDate = (clone $startDate)->addDays(fake()->numberBetween(1, 14));

        $data = [
            'name' => fake()->company().' Convention',
            'city' => fake()->unique()->city(),
            'country' => fake()->unique()->country(),
            'start_date' => $startDate->format('Y-m-d'),
            'end_date' => $endDate->format('Y-m-d'),
            'address' => fake()->address(),
            'other_info' => fake()->paragraph(),
        ];

        $response = $this->post(route('conventions.store'), $data);

        $response->assertSessionHasNoErrors();
        $response->assertRedirect();

        $this->assertDatabaseHas('conventions', [
            'name' => $data['name'],
            'city' => $data['city'],
            'country' => $data['country'],
            'address' => $data['address'],
        ]);
    }
})->group('property', 'convention-creation');

it('creates convention successfully without any optional fields', function () {
    actingAs($this->user);

    for ($iteration = 0; $iteration < 3; $iteration++) {
        $startDate = now()->addDays(fake()->numberBetween(1, 60));
        $endDate = (clone $startDate)->addDays(fake()->numberBetween(1, 14));

        $data = [
            'name' => fake()->company().' Convention',
            'city' => fake()->unique()->city(),
            'country' => fake()->unique()->country(),
            'start_date' => $startDate->format('Y-m-d'),
            'end_date' => $endDate->format('Y-m-d'),
        ];

        $response = $this->post(route('conventions.store'), $data);

        $response->assertSessionHasNoErrors();
        $response->assertRedirect();

        $this->assertDatabaseHas('conventions', [
            'name' => $data['name'],
            'city' => $data['city'],
            'country' => $data['country'],
        ]);
    }
})->group('property', 'convention-creation');

it('creates convention with random combinations of optional fields', function () {
    actingAs($this->user);

    $optionalCombinations = [
        ['address' => true, 'other_info' => true],
        ['address' => true, 'other_info' => false],
        ['address' => false, 'other_info' => true],
        ['address' => false, 'other_info' => false],
    ];

    foreach ($optionalCombinations as $idx => $combo) {
        for ($iteration = 0; $iteration < 2; $iteration++) {
            $startDate = now()->addDays(fake()->numberBetween(1, 60));
            $endDate = (clone $startDate)->addDays(fake()->numberBetween(1, 14));

            $data = [
                'name' => fake()->company().' Convention',
                'city' => fake()->unique()->city(),
                'country' => fake()->unique()->country(),
                'start_date' => $startDate->format('Y-m-d'),
                'end_date' => $endDate->format('Y-m-d'),
            ];

            if ($combo['address']) {
                $data['address'] = fake()->address();
            }

            if ($combo['other_info']) {
                $data['other_info'] = fake()->paragraph();
            }

            $response = $this->post(route('conventions.store'), $data);

            $response->assertSessionHasNoErrors();
            $response->assertRedirect();

            $this->assertDatabaseHas('conventions', [
                'name' => $data['name'],
                'city' => $data['city'],
                'country' => $data['country'],
            ]);
        }
    }
})->group('property', 'convention-creation');
