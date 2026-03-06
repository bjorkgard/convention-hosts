<?php

// Feature: guest-convention-email-verification, Property 4: Verification email contains signed URL and user context

use App\Mail\GuestConventionVerification;
use App\Models\User;
use Illuminate\Support\Facades\Mail;

/**
 * Property 4: Verification email contains signed URL and user context
 *
 * For any new user created during guest convention creation, a verification email
 * should be sent to their email address containing a signed URL to the set password
 * page, the user's first name, and the convention name.
 *
 * **Validates: Requirements 3.1, 3.2, 3.4**
 */
it('sends verification email with signed URL, user first name, and convention name across 100+ iterations', function () {
    for ($i = 0; $i < 100; $i++) {
        Mail::fake();

        $startDate = now()->addDays(fake()->numberBetween(1, 60));
        $endDate = (clone $startDate)->addDays(fake()->numberBetween(1, 14));

        $data = [
            'first_name' => fake()->firstName(),
            'last_name' => fake()->lastName(),
            'email' => fake()->unique()->safeEmail(),
            'mobile' => fake()->phoneNumber(),
            'name' => fake()->company().' Convention',
            'city' => fake()->unique()->city(),
            'country' => fake()->unique()->country(),
            'start_date' => $startDate->format('Y-m-d'),
            'end_date' => $endDate->format('Y-m-d'),
        ];

        // Ensure email does not exist
        expect(User::where('email', $data['email'])->exists())->toBeFalse();

        $this->post(route('conventions.guest.store'), $data);

        // Assert the verification email was sent to the correct recipient
        Mail::assertSent(GuestConventionVerification::class, function (GuestConventionVerification $mail) use ($data) {
            // Verify recipient
            if (! $mail->hasTo($data['email'])) {
                return false;
            }

            // Verify user first name is available on the mailable
            if ($mail->user->first_name !== $data['first_name']) {
                return false;
            }

            // Verify convention name is available on the mailable
            if ($mail->convention->name !== $data['name']) {
                return false;
            }

            // Verify the verification URL is a signed URL containing the expected route
            if (empty($mail->verificationUrl)) {
                return false;
            }

            if (! str_contains($mail->verificationUrl, 'guest-verification')) {
                return false;
            }

            if (! str_contains($mail->verificationUrl, 'signature=')) {
                return false;
            }

            return true;
        });
    }
})->group('property', 'guest-convention-verification');
