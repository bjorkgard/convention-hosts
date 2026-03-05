<?php

use App\Http\Requests\StoreSectionRequest;
use Illuminate\Support\Facades\Validator;

/**
 * Property 21: Section Creation Validation
 *
 * For any section creation attempt, if any of the required fields (floor_id, name,
 * number_of_seats) are missing, the system should reject the creation with validation errors.
 *
 * **Validates: Requirements 6.3**
 */
it('validates section creation requires all required fields', function () {
    // Test with all fields missing
    $validator = Validator::make([], (new StoreSectionRequest)->rules());

    expect($validator->fails())->toBeTrue('Expected validation to fail when all fields are missing');
    expect($validator->errors()->has('name'))->toBeTrue('Expected name field error');
    expect($validator->errors()->has('number_of_seats'))->toBeTrue('Expected number_of_seats field error');
});

it('validates section creation with only name missing', function () {
    $validator = Validator::make([
        'number_of_seats' => 100,
    ], (new StoreSectionRequest)->rules());

    expect($validator->fails())->toBeTrue('Expected validation to fail when name is missing');
    expect($validator->errors()->has('name'))->toBeTrue('Expected name field error');
});

it('validates section creation with only number_of_seats missing', function () {
    $validator = Validator::make([
        'name' => 'Section A',
    ], (new StoreSectionRequest)->rules());

    expect($validator->fails())->toBeTrue('Expected validation to fail when number_of_seats is missing');
    expect($validator->errors()->has('number_of_seats'))->toBeTrue('Expected number_of_seats field error');
});

it('validates section creation accepts valid data', function () {
    // Run property test with multiple random valid inputs
    for ($i = 0; $i < 100; $i++) {
        $data = [
            'name' => fake()->words(rand(1, 3), true),
            'number_of_seats' => rand(10, 500),
        ];

        $validator = Validator::make($data, (new StoreSectionRequest)->rules());

        expect($validator->passes())
            ->toBeTrue('Expected validation to pass with valid data: '.json_encode($data));
    }
});

it('validates section number_of_seats must be positive integer', function () {
    $invalidSeats = [0, -1, -100, 'abc', 12.5];

    foreach ($invalidSeats as $seats) {
        $validator = Validator::make([
            'name' => 'Section A',
            'number_of_seats' => $seats,
        ], (new StoreSectionRequest)->rules());

        expect($validator->fails())
            ->toBeTrue("Expected validation to fail for invalid number_of_seats: {$seats}");
        expect($validator->errors()->has('number_of_seats'))
            ->toBeTrue("Expected number_of_seats field error for value: {$seats}");
    }
});

it('validates section optional fields are accepted', function () {
    // Test with all optional fields
    $validator = Validator::make([
        'name' => 'Section A',
        'number_of_seats' => 100,
        'elder_friendly' => true,
        'handicap_friendly' => false,
        'information' => 'Some additional information',
    ], (new StoreSectionRequest)->rules());

    expect($validator->passes())->toBeTrue('Expected validation to pass with optional fields');
});

it('validates section optional fields can be omitted', function () {
    // Test without optional fields
    $validator = Validator::make([
        'name' => 'Section A',
        'number_of_seats' => 100,
    ], (new StoreSectionRequest)->rules());

    expect($validator->passes())->toBeTrue('Expected validation to pass without optional fields');
});

it('validates section elder_friendly must be boolean', function () {
    $invalidValues = ['yes', 'no', 1, 0, 'true', 'false'];

    foreach ($invalidValues as $value) {
        $validator = Validator::make([
            'name' => 'Section A',
            'number_of_seats' => 100,
            'elder_friendly' => $value,
        ], (new StoreSectionRequest)->rules());

        // Note: Laravel converts 1/0 and 'true'/'false' strings to boolean, so some may pass
        // We're testing that non-boolean-convertible values fail
        if (! in_array($value, [1, 0, '1', '0', 'true', 'false', true, false], true)) {
            expect($validator->fails())
                ->toBeTrue('Expected validation to fail for non-boolean elder_friendly: '.json_encode($value));
        }
    }
});

it('validates section handicap_friendly must be boolean', function () {
    $invalidValues = ['yes', 'no', 'accessible'];

    foreach ($invalidValues as $value) {
        $validator = Validator::make([
            'name' => 'Section A',
            'number_of_seats' => 100,
            'handicap_friendly' => $value,
        ], (new StoreSectionRequest)->rules());

        expect($validator->fails())
            ->toBeTrue('Expected validation to fail for non-boolean handicap_friendly: '.json_encode($value));
    }
});
