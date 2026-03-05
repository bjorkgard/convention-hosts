<?php

use App\Mail\EmailConfirmation;
use App\Models\User;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\URL;

/**
 * Property 9: Email Update Triggers Confirmation
 *
 * For any existing user with a confirmed email, when the email is changed
 * to any new valid email, the system sets email_confirmed to false and
 * sends exactly one EmailConfirmation email to the new address.
 *
 * When a user is updated without changing the email, no confirmation
 * email is sent and email_confirmed remains unchanged.
 *
 * Validates: Requirements 3.5
 */
it('sends confirmation email and resets email_confirmed when email is changed', function () {
    for ($i = 0; $i < 50; $i++) {
        Mail::fake();

        URL::shouldReceive('temporarySignedRoute')
            ->once()
            ->withArgs(function (string $route, $expiration, array $params) {
                return $route === 'email.confirm' && isset($params['user']);
            })
            ->andReturn('https://example.com/email/confirm?signature=test-sig-'.$i);

        $user = User::factory()->create([
            'email_confirmed' => true,
        ]);

        $oldEmail = $user->email;
        $newEmail = 'updated-'.$i.'-'.fake()->unique()->numberBetween(1000, 99999).'@example.com';

        // Act: update the email
        $user->update(['email' => $newEmail]);

        // Property 1: email_confirmed is set to false
        $user->refresh();
        expect($user->email_confirmed)->toBeFalse();

        // Property 2: Exactly one email was sent
        Mail::assertSentCount(1);

        // Property 3: The email is an EmailConfirmation mailable
        Mail::assertSent(EmailConfirmation::class);

        // Property 4: The email was sent to the NEW email address
        Mail::assertSent(EmailConfirmation::class, function (EmailConfirmation $mail) use ($newEmail) {
            return $mail->hasTo($newEmail);
        });

        // Property 5: The mailable contains the correct user
        Mail::assertSent(EmailConfirmation::class, function (EmailConfirmation $mail) use ($user) {
            return $mail->user->id === $user->id;
        });

        // Cleanup
        $user->delete();
        Mockery::close();
    }
})->group('property', 'email');

it('does not send confirmation email when email is not changed', function () {
    for ($i = 0; $i < 50; $i++) {
        Mail::fake();

        $user = User::factory()->create([
            'email_confirmed' => true,
        ]);

        // Act: update non-email fields only
        $user->update([
            'first_name' => fake()->firstName(),
            'last_name' => fake()->lastName(),
        ]);

        // Property 1: No emails sent
        Mail::assertNothingSent();

        // Property 2: email_confirmed remains true
        $user->refresh();
        expect($user->email_confirmed)->toBeTrue();

        // Cleanup
        $user->delete();
    }
})->group('property', 'email');
