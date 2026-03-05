<?php

use App\Models\Convention;
use App\Models\User;
use Illuminate\Support\Facades\Validator;

/**
 * Property 3: Convention Date Overlap Detection
 *
 * For any two conventions in the same city and country, if their date ranges overlap
 * (start_date to end_date), the system should reject the creation of the second
 * convention with a validation error.
 *
 * **Validates: Requirements 1.3**
 */
it('validates convention date overlap detection', function () {
    $user = User::factory()->create();

    // Run property test with multiple random scenarios
    for ($i = 0; $i < 100; $i++) {
        // Create first convention with random dates in the future
        $city = fake()->city();
        $country = fake()->country();
        $baseDate = now()->addDays(rand(10, 60)); // Start at least 10 days in the future
        $startDate1 = clone $baseDate;
        $endDate1 = (clone $startDate1)->addDays(rand(3, 10));

        $convention1 = Convention::factory()->create([
            'city' => $city,
            'country' => $country,
            'start_date' => $startDate1,
            'end_date' => $endDate1,
        ]);

        // Test overlapping scenarios
        $overlapScenarios = [
            // Scenario 1: Second convention starts during first convention
            [
                'start_date' => (clone $startDate1)->addDays(rand(1, 3)),
                'end_date' => (clone $endDate1)->addDays(rand(1, 5)),
                'should_fail' => true,
            ],
            // Scenario 2: Second convention ends during first convention
            [
                'start_date' => (clone $startDate1)->subDays(rand(1, 3)),
                'end_date' => (clone $startDate1)->addDays(rand(1, 2)),
                'should_fail' => true,
            ],
            // Scenario 3: Second convention completely contains first convention
            [
                'start_date' => (clone $startDate1)->subDays(rand(1, 3)),
                'end_date' => (clone $endDate1)->addDays(rand(1, 3)),
                'should_fail' => true,
            ],
            // Scenario 4: Second convention is completely contained by first convention
            [
                'start_date' => (clone $startDate1)->addDays(1),
                'end_date' => (clone $endDate1)->subDays(1),
                'should_fail' => true,
            ],
            // Scenario 5: Second convention is after first convention (no overlap)
            [
                'start_date' => (clone $endDate1)->addDays(rand(1, 5)),
                'end_date' => (clone $endDate1)->addDays(rand(6, 10)),
                'should_fail' => false,
            ],
            // Scenario 6: Second convention is before first convention (no overlap)
            [
                'start_date' => (clone $startDate1)->subDays(rand(6, 9)),
                'end_date' => (clone $startDate1)->subDays(rand(1, 5)),
                'should_fail' => false,
            ],
        ];

        foreach ($overlapScenarios as $scenario) {
            $data = [
                'name' => fake()->sentence(3),
                'city' => $city,
                'country' => $country,
                'start_date' => $scenario['start_date']->format('Y-m-d'),
                'end_date' => $scenario['end_date']->format('Y-m-d'),
            ];

            // Create a validator with the rules
            $validator = Validator::make($data, (new \App\Http\Requests\StoreConventionRequest)->rules());

            // Manually trigger the withValidator logic
            $request = new \App\Http\Requests\StoreConventionRequest;
            $request->merge($data);
            $request->setContainer(app());
            $request->withValidator($validator);

            // Trigger validation by calling fails() or passes()
            $validationFailed = $validator->fails();

            if ($scenario['should_fail']) {
                expect($validationFailed)
                    ->toBeTrue("Expected validation to fail for overlapping dates: {$scenario['start_date']->format('Y-m-d')} to {$scenario['end_date']->format('Y-m-d')}");
                expect($validator->errors()->has('start_date'))
                    ->toBeTrue('Expected start_date error for overlapping convention');
            } else {
                expect($validationFailed)
                    ->toBeFalse("Expected validation to pass for non-overlapping dates: {$scenario['start_date']->format('Y-m-d')} to {$scenario['end_date']->format('Y-m-d')}");
            }
        }

        // Clean up for next iteration
        $convention1->delete();
    }
});

it('allows conventions in different cities to have overlapping dates', function () {
    $user = User::factory()->create();

    $startDate = now()->addDays(5);
    $endDate = now()->addDays(10);

    // Create first convention in City A
    Convention::factory()->create([
        'city' => 'City A',
        'country' => 'Country X',
        'start_date' => $startDate,
        'end_date' => $endDate,
    ]);

    // Create second convention in City B with same dates - should succeed
    $validator = Validator::make([
        'name' => fake()->sentence(3),
        'city' => 'City B',
        'country' => 'Country X',
        'start_date' => $startDate->format('Y-m-d'),
        'end_date' => $endDate->format('Y-m-d'),
    ], (new \App\Http\Requests\StoreConventionRequest)->rules());

    $request = new \App\Http\Requests\StoreConventionRequest;
    $request->merge([
        'city' => 'City B',
        'country' => 'Country X',
        'start_date' => $startDate->format('Y-m-d'),
        'end_date' => $endDate->format('Y-m-d'),
    ]);
    $request->withValidator($validator);

    expect($validator->fails())->toBeFalse('Expected validation to pass for different cities');
});

it('allows conventions in different countries to have overlapping dates', function () {
    $user = User::factory()->create();

    $startDate = now()->addDays(5);
    $endDate = now()->addDays(10);

    // Create first convention in Country X
    Convention::factory()->create([
        'city' => 'City A',
        'country' => 'Country X',
        'start_date' => $startDate,
        'end_date' => $endDate,
    ]);

    // Create second convention in Country Y with same dates - should succeed
    $validator = Validator::make([
        'name' => fake()->sentence(3),
        'city' => 'City A',
        'country' => 'Country Y',
        'start_date' => $startDate->format('Y-m-d'),
        'end_date' => $endDate->format('Y-m-d'),
    ], (new \App\Http\Requests\StoreConventionRequest)->rules());

    $request = new \App\Http\Requests\StoreConventionRequest;
    $request->merge([
        'city' => 'City A',
        'country' => 'Country Y',
        'start_date' => $startDate->format('Y-m-d'),
        'end_date' => $endDate->format('Y-m-d'),
    ]);
    $request->withValidator($validator);

    expect($validator->fails())->toBeFalse('Expected validation to pass for different countries');
});
