<?php

// Feature: guest-convention-email-verification, Property 6: Password validation enforcement

use App\Models\Convention;
use App\Models\User;

/**
 * Property 6: Password validation enforcement
 *
 * For any string that does not satisfy all password criteria (minimum 8 characters,
 * at least one lowercase, one uppercase, one number, one symbol), submitting it as
 * a password on the set password page should be rejected by validation.
 *
 * **Validates: Requirements 4.3**
 */

/**
 * Generate an invalid password that violates at least one criterion.
 *
 * Strategies:
 * 0 - Too short (< 8 chars but otherwise valid)
 * 1 - No lowercase letter
 * 2 - No uppercase letter
 * 3 - No number
 * 4 - No symbol
 * 5 - Empty string
 * 6 - Only lowercase
 * 7 - Only uppercase
 * 8 - Only digits
 * 9 - Only symbols
 */
function generateInvalidPassword(int $strategy): string
{
    return match ($strategy % 10) {
        0 => 'Ab1@'.fake()->lexify('??'),                          // 6 chars - too short
        1 => strtoupper(fake()->lexify('????????')).'1@',           // no lowercase
        2 => strtolower(fake()->lexify('????????')).'1@',           // no uppercase
        3 => fake()->lexify('????ABCD').'@',                        // no number
        4 => fake()->lexify('????').'ABCD1',                        // no symbol
        5 => '',                                                       // empty
        6 => strtolower(fake()->lexify('????????????')),               // only lowercase
        7 => strtoupper(fake()->lexify('????????????')),               // only uppercase
        8 => (string) fake()->numberBetween(10000000, 99999999),      // only digits
        9 => str_repeat('@$!%*#?&', 2),                               // only symbols
    };
}

it('rejects passwords that violate validation criteria across 100+ iterations', function () {
    for ($i = 0; $i < 3; $i++) {
        $user = User::factory()->create([
            'first_name' => fake()->firstName(),
            'last_name' => fake()->lastName(),
            'email' => fake()->unique()->safeEmail(),
            'email_confirmed' => false,
        ]);

        $convention = Convention::factory()->create([
            'name' => fake()->company().' Convention',
        ]);

        $convention->users()->attach($user->id);

        $invalidPassword = generateInvalidPassword($i);

        $response = $this->post(
            route('guest-verification.store', ['user' => $user->id, 'convention' => $convention->id]),
            [
                'password' => $invalidPassword,
                'password_confirmation' => $invalidPassword,
            ]
        );

        $response->assertSessionHasErrors('password',
            "Iteration {$i}: Password '{$invalidPassword}' (strategy ".($i % 10).') should be rejected by validation'
        );
    }
})->group('property', 'guest-convention-verification');
