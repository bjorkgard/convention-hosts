<?php

use App\Http\Requests\SetPasswordRequest;
use Illuminate\Support\Facades\Validator;

/**
 * Property 50: Password Validation Criteria
 *
 * For any password submission, if it does not meet the criteria (minimum 8 characters,
 * at least one lowercase, one uppercase, one number, one symbol), the system should
 * reject it with a validation error.
 *
 * **Validates: Requirements 21.4**
 */
it('validates password requires minimum 8 characters', function () {
    $shortPasswords = ['Ab1@', 'Test1!', 'aB3$', 'Xy9#'];

    foreach ($shortPasswords as $password) {
        $validator = Validator::make([
            'password' => $password,
            'password_confirmation' => $password,
        ], (new SetPasswordRequest)->rules());

        expect($validator->fails())
            ->toBeTrue("Expected validation to fail for password shorter than 8 characters: {$password}");
        expect($validator->errors()->has('password'))
            ->toBeTrue("Expected password field error for: {$password}");
    }
});

it('validates password requires lowercase letter', function () {
    $passwords = ['ABCDEF12@', 'TEST1234!', 'UPPER999#'];

    foreach ($passwords as $password) {
        $validator = Validator::make([
            'password' => $password,
            'password_confirmation' => $password,
        ], (new SetPasswordRequest)->rules());

        expect($validator->fails())
            ->toBeTrue("Expected validation to fail for password without lowercase: {$password}");
    }
});

it('validates password requires uppercase letter', function () {
    $passwords = ['abcdef12@', 'test1234!', 'lower999#'];

    foreach ($passwords as $password) {
        $validator = Validator::make([
            'password' => $password,
            'password_confirmation' => $password,
        ], (new SetPasswordRequest)->rules());

        expect($validator->fails())
            ->toBeTrue("Expected validation to fail for password without uppercase: {$password}");
    }
});

it('validates password requires number', function () {
    $passwords = ['Abcdefgh@', 'TestPass!', 'UpperLow#'];

    foreach ($passwords as $password) {
        $validator = Validator::make([
            'password' => $password,
            'password_confirmation' => $password,
        ], (new SetPasswordRequest)->rules());

        expect($validator->fails())
            ->toBeTrue("Expected validation to fail for password without number: {$password}");
    }
});

it('validates password requires symbol', function () {
    $passwords = ['Abcdef12', 'Test1234', 'Upper999'];

    foreach ($passwords as $password) {
        $validator = Validator::make([
            'password' => $password,
            'password_confirmation' => $password,
        ], (new SetPasswordRequest)->rules());

        expect($validator->fails())
            ->toBeTrue("Expected validation to fail for password without symbol: {$password}");
    }
});

it('validates password requires confirmation', function () {
    $validator = Validator::make([
        'password' => 'ValidPass123!',
        'password_confirmation' => 'DifferentPass123!',
    ], (new SetPasswordRequest)->rules());

    expect($validator->fails())->toBeTrue('Expected validation to fail when passwords do not match');
    expect($validator->errors()->has('password'))->toBeTrue('Expected password field error');
});

it('validates password accepts valid passwords', function () {
    // Run property test with multiple valid passwords
    $validPasswords = [
        'Password123!',
        'SecureP@ss1',
        'MyP@ssw0rd',
        'Test1234@',
        'Valid#Pass1',
        'Strong$123',
        'Complex!9aB',
        'Secure&Pass1',
    ];

    foreach ($validPasswords as $password) {
        $validator = Validator::make([
            'password' => $password,
            'password_confirmation' => $password,
        ], (new SetPasswordRequest)->rules());

        expect($validator->passes())
            ->toBeTrue("Expected validation to pass for valid password: {$password}");
    }
});

it('validates password with all criteria combinations', function () {
    // Property test: Generate 100 random valid passwords
    for ($i = 0; $i < 100; $i++) {
        // Generate a password that meets all criteria
        $lowercase = chr(rand(97, 122)); // a-z
        $uppercase = chr(rand(65, 90));  // A-Z
        $number = rand(0, 9);
        $symbols = ['@', '$', '!', '%', '*', '#', '?', '&'];
        $symbol = $symbols[array_rand($symbols)];

        // Add random characters to reach minimum length
        $extraChars = '';
        for ($j = 0; $j < rand(4, 10); $j++) {
            $type = rand(0, 3);
            if ($type === 0) {
                $extraChars .= chr(rand(97, 122)); // lowercase
            } elseif ($type === 1) {
                $extraChars .= chr(rand(65, 90)); // uppercase
            } elseif ($type === 2) {
                $extraChars .= rand(0, 9); // number
            } else {
                $extraChars .= $symbols[array_rand($symbols)]; // symbol
            }
        }

        $password = $lowercase.$uppercase.$number.$symbol.$extraChars;
        $password = str_shuffle($password); // Shuffle to randomize position

        $validator = Validator::make([
            'password' => $password,
            'password_confirmation' => $password,
        ], (new SetPasswordRequest)->rules());

        expect($validator->passes())
            ->toBeTrue("Expected validation to pass for generated valid password: {$password}");
    }
});

it('validates password rejects passwords missing any single criterion', function () {
    // Test passwords missing exactly one criterion
    $invalidPasswords = [
        'UPPERCASE123!' => 'missing lowercase',
        'lowercase123!' => 'missing uppercase',
        'PasswordTest!' => 'missing number',
        'Password123' => 'missing symbol',
        'Short1!' => 'too short',
    ];

    foreach ($invalidPasswords as $password => $reason) {
        $validator = Validator::make([
            'password' => $password,
            'password_confirmation' => $password,
        ], (new SetPasswordRequest)->rules());

        expect($validator->fails())
            ->toBeTrue("Expected validation to fail for password {$reason}: {$password}");
    }
});
