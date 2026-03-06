<?php

use App\Http\Requests\StoreUserRequest;
use Illuminate\Support\Facades\Validator;

/**
 * Property 13: Email Domain Restriction
 *
 * For any email address containing "jwpub.org", user creation should be rejected
 * with a validation error.
 *
 * **Validates: Requirements 4.2**
 */
it('validates email domain restriction for jwpub.org', function () {
    $invalidEmails = [
        'user@jwpub.org',
        'test@JWPUB.ORG',
        'admin@jwpub.org.uk',
        'contact@subdomain.jwpub.org',
        'info@jwpub.org.com',
    ];

    foreach ($invalidEmails as $email) {
        $validator = Validator::make([
            'first_name' => 'John',
            'last_name' => 'Doe',
            'email' => $email,
            'mobile' => '+1234567890',
            'roles' => ['ConventionUser'],
        ], (new StoreUserRequest)->rules());

        expect($validator->fails())
            ->toBeTrue("Expected validation to fail for email: {$email}");
        expect($validator->errors()->has('email'))
            ->toBeTrue("Expected email field error for: {$email}");
    }
});

it('validates email domain allows other domains', function () {
    // Run property test with multiple random valid emails
    for ($i = 0; $i < 3; $i++) {
        $email = fake()->unique()->safeEmail();

        // Skip if by chance it contains jwpub.org
        if (stripos($email, 'jwpub.org') !== false) {
            continue;
        }

        $validator = Validator::make([
            'first_name' => fake()->firstName(),
            'last_name' => fake()->lastName(),
            'email' => $email,
            'mobile' => fake()->phoneNumber(),
            'roles' => ['ConventionUser'],
        ], (new StoreUserRequest)->rules());

        expect($validator->passes())
            ->toBeTrue("Expected validation to pass for valid email: {$email}");
    }
});

it('validates user creation requires all required fields', function () {
    $validator = Validator::make([], (new StoreUserRequest)->rules());

    expect($validator->fails())->toBeTrue('Expected validation to fail when all fields are missing');
    expect($validator->errors()->has('first_name'))->toBeTrue('Expected first_name field error');
    expect($validator->errors()->has('last_name'))->toBeTrue('Expected last_name field error');
    expect($validator->errors()->has('email'))->toBeTrue('Expected email field error');
    expect($validator->errors()->has('mobile'))->toBeTrue('Expected mobile field error');
    expect($validator->errors()->has('roles'))->toBeTrue('Expected roles field error');
});

it('validates roles array must contain at least one role', function () {
    $validator = Validator::make([
        'first_name' => 'John',
        'last_name' => 'Doe',
        'email' => 'john@example.com',
        'mobile' => '+1234567890',
        'roles' => [],
    ], (new StoreUserRequest)->rules());

    expect($validator->fails())->toBeTrue('Expected validation to fail for empty roles array');
    expect($validator->errors()->has('roles'))->toBeTrue('Expected roles field error');
});

it('validates roles must be valid role types', function () {
    $invalidRoles = ['Admin', 'SuperUser', 'Guest', 'InvalidRole'];

    foreach ($invalidRoles as $role) {
        $validator = Validator::make([
            'first_name' => 'John',
            'last_name' => 'Doe',
            'email' => 'john@example.com',
            'mobile' => '+1234567890',
            'roles' => [$role],
        ], (new StoreUserRequest)->rules());

        expect($validator->fails())
            ->toBeTrue("Expected validation to fail for invalid role: {$role}");
    }
});

it('validates FloorUser role requires floor_ids', function () {
    $data = [
        'first_name' => 'John',
        'last_name' => 'Doe',
        'email' => 'john@example.com',
        'mobile' => '+1234567890',
        'roles' => ['FloorUser'],
    ];

    $validator = Validator::make($data, (new StoreUserRequest)->rules());

    $request = new StoreUserRequest;
    $request->merge($data);
    $request->setContainer(app());
    $request->withValidator($validator);

    $validationFailed = $validator->fails();

    expect($validationFailed)->toBeTrue('Expected validation to fail when FloorUser has no floor_ids');
    expect($validator->errors()->has('floor_ids'))->toBeTrue('Expected floor_ids field error');
});

it('validates SectionUser role requires section_ids', function () {
    $data = [
        'first_name' => 'John',
        'last_name' => 'Doe',
        'email' => 'john@example.com',
        'mobile' => '+1234567890',
        'roles' => ['SectionUser'],
    ];

    $validator = Validator::make($data, (new StoreUserRequest)->rules());

    $request = new StoreUserRequest;
    $request->merge($data);
    $request->setContainer(app());
    $request->withValidator($validator);

    $validationFailed = $validator->fails();

    expect($validationFailed)->toBeTrue('Expected validation to fail when SectionUser has no section_ids');
    expect($validator->errors()->has('section_ids'))->toBeTrue('Expected section_ids field error');
});
