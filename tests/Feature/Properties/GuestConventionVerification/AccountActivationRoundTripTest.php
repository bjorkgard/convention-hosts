<?php

// Feature: guest-convention-email-verification, Property 7: Account activation round trip

use App\Models\Convention;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

/**
 * Property 7: Account activation round trip
 *
 * For any valid password submission on the set password page, the system should
 * save the hashed password to the user record, set email_confirmed to true,
 * log the user in, and redirect to the convention show page.
 *
 * **Validates: Requirements 5.1, 5.2, 5.3, 5.4**
 */

/**
 * Generate a valid password that meets all criteria:
 * - Minimum 8 characters
 * - At least one lowercase letter
 * - At least one uppercase letter
 * - At least one number
 * - At least one symbol (@$!%*#?&)
 */
function generateValidPassword(): string
{
    return fake()->lexify('????').'A1@'.fake()->lexify('?');
}

it('activates account with hashed password, confirmed email, login, and redirect across 100+ iterations', function () {
    for ($i = 0; $i < 100; $i++) {
        $user = User::factory()->create([
            'first_name' => fake()->firstName(),
            'last_name' => fake()->lastName(),
            'email' => fake()->unique()->safeEmail(),
            'password' => Hash::make(\Illuminate\Support\Str::random(32)),
            'email_confirmed' => false,
        ]);

        $convention = Convention::factory()->create([
            'name' => fake()->company().' Convention',
        ]);

        $convention->users()->attach($user->id);

        $validPassword = generateValidPassword();

        $response = $this->post(
            route('guest-verification.store', ['user' => $user->id, 'convention' => $convention->id]),
            [
                'password' => $validPassword,
                'password_confirmation' => $validPassword,
            ]
        );

        // Requirement 5.4: Redirect to convention show page
        $response->assertRedirect(route('conventions.show', $convention));

        // Refresh user from database
        $user->refresh();

        // Requirement 5.1: Hashed password saved
        expect(Hash::check($validPassword, $user->password))
            ->toBeTrue("Iteration {$i}: Password should be hashed and saved correctly");

        // Requirement 5.2: email_confirmed set to true
        expect($user->email_confirmed)
            ->toBeTrue("Iteration {$i}: email_confirmed should be true after activation");

        // Requirement 5.3: User logged in
        expect(Auth::check())
            ->toBeTrue("Iteration {$i}: User should be logged in after activation");
        expect(Auth::id())
            ->toBe($user->id, "Iteration {$i}: Logged in user should match the activated user");

        // Logout for next iteration
        Auth::logout();
    }
})->group('property', 'guest-convention-verification');
