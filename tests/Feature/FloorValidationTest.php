<?php

use App\Http\Requests\StoreFloorRequest;
use Illuminate\Support\Facades\Validator;

/**
 * Property 19: Floor Creation Validation
 *
 * For any floor creation attempt, if the name field is missing, the system should
 * reject the creation with a validation error.
 *
 * **Validates: Requirements 6.1**
 */
it('validates floor creation requires name field', function () {
    // Test with missing name
    $validator = Validator::make([], (new StoreFloorRequest)->rules());

    expect($validator->fails())->toBeTrue('Expected validation to fail when name is missing');
    expect($validator->errors()->has('name'))->toBeTrue('Expected name field error');
});

it('validates floor creation accepts valid name', function () {
    // Run property test with multiple random names
    for ($i = 0; $i < 3; $i++) {
        $name = fake()->words(rand(1, 5), true);

        $validator = Validator::make([
            'name' => $name,
        ], (new StoreFloorRequest)->rules());

        expect($validator->passes())
            ->toBeTrue("Expected validation to pass with valid name: {$name}");
    }
});

it('validates floor name cannot exceed 255 characters', function () {
    $longName = str_repeat('a', 256);

    $validator = Validator::make([
        'name' => $longName,
    ], (new StoreFloorRequest)->rules());

    expect($validator->fails())->toBeTrue('Expected validation to fail for name exceeding 255 characters');
    expect($validator->errors()->has('name'))->toBeTrue('Expected name field error');
});

it('validates floor name must be a string', function () {
    $invalidNames = [123, 45.67, true, ['array'], null];

    foreach ($invalidNames as $invalidName) {
        $validator = Validator::make([
            'name' => $invalidName,
        ], (new StoreFloorRequest)->rules());

        expect($validator->fails())
            ->toBeTrue('Expected validation to fail for non-string name: '.json_encode($invalidName));
    }
});
