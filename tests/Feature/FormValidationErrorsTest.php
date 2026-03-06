<?php

use App\Models\User;

/**
 * Property 51: Validation Error Display
 *
 * For any form submission that fails validation, the system should return
 * inline error messages via Inertia for each invalid field.
 *
 * **Validates: Requirements 24.2**
 */
it('returns validation errors for each invalid field when convention creation fails', function () {
    $user = User::factory()->create();

    for ($i = 0; $i < 3; $i++) {
        $startDate = now()->addDays(rand(5, 30));
        $endDate = (clone $startDate)->addDays(rand(1, 10));

        // Build a full valid data set, then selectively invalidate one field at a time
        $validData = [
            'name' => fake()->sentence(3),
            'city' => fake()->city(),
            'country' => fake()->country(),
            'start_date' => $startDate->format('Y-m-d'),
            'end_date' => $endDate->format('Y-m-d'),
        ];

        // Pick a random required field to invalidate
        $requiredFields = ['name', 'city', 'country', 'start_date', 'end_date'];
        $fieldToInvalidate = fake()->randomElement($requiredFields);

        $submissionData = $validData;

        // Invalidate the chosen field with an empty string (which Laravel converts to null)
        $submissionData[$fieldToInvalidate] = '';

        $response = $this->actingAs($user)->post(route('conventions.store'), $submissionData);

        // Should redirect back (validation failure via Inertia)
        $response->assertStatus(302);

        // The invalidated field should have a validation error in the session
        $response->assertSessionHasErrors([$fieldToInvalidate]);

        // Other fields should NOT have errors
        $otherFields = array_diff($requiredFields, [$fieldToInvalidate]);
        foreach ($otherFields as $field) {
            $response->assertSessionDoesntHaveErrors([$field]);
        }
    }
})->group('property', 'convention', 'validation');

/**
 * Property 52: Form Input Preservation
 *
 * For any form submission that fails validation, the user's input should be
 * preserved and redisplayed in the form.
 *
 * **Validates: Requirements 24.4**
 */
it('preserves user input in session when convention creation validation fails', function () {
    $user = User::factory()->create();

    for ($i = 0; $i < 3; $i++) {
        $startDate = now()->addDays(rand(5, 30));
        $endDate = (clone $startDate)->addDays(rand(1, 10));

        // Submit with all fields present but name empty to trigger validation failure
        // This ensures the overlap check doesn't crash (dates are valid)
        $inputData = [
            'name' => '', // Empty to trigger required validation failure
            'city' => fake()->city(),
            'country' => fake()->country(),
            'start_date' => $startDate->format('Y-m-d'),
            'end_date' => $endDate->format('Y-m-d'),
            'address' => fake()->boolean() ? fake()->address() : null,
            'other_info' => fake()->boolean() ? fake()->sentence() : null,
        ];

        // Remove null optional fields (simulates user not filling them in)
        $inputData = array_filter($inputData, fn ($v) => $v !== null);

        $response = $this->actingAs($user)->post(route('conventions.store'), $inputData);

        // Should redirect back with errors
        $response->assertStatus(302);
        $response->assertSessionHasErrors(['name']);

        // Verify that submitted input values are preserved in the session (old input)
        // Note: Laravel's ConvertEmptyStringsToNull middleware converts '' to null,
        // so we check that old input exists for non-empty fields
        $preservableFields = array_filter($inputData, fn ($v) => $v !== '');
        foreach ($preservableFields as $key => $value) {
            $oldValue = session()->getOldInput($key);
            expect($oldValue)->toBe(
                $value,
                "Expected old input for '{$key}' to be preserved, got: ".var_export($oldValue, true)
            );
        }
    }
})->group('property', 'convention', 'validation');
