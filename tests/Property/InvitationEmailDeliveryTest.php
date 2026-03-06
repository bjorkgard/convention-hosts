<?php

use App\Actions\InviteUserAction;
use App\Mail\UserInvitation;
use App\Models\Convention;
use App\Models\User;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\URL;

/**
 * Property 7: User Invitation Email Delivery
 *
 * For any newly created user, the system should send an invitation email
 * containing a signed URL that expires in 24 hours.
 *
 * Validates: Requirements 3.1, 3.2
 */
it('sends exactly one invitation email to the correct recipient for any valid user data', function () {
    for ($i = 0; $i < 3; $i++) {
        Mail::fake();

        // Mock URL generation to return a signed-looking URL
        URL::shouldReceive('temporarySignedRoute')
            ->once()
            ->withArgs(function (string $route, $expiration, array $params) {
                return $route === 'invitation.show'
                    && isset($params['user'])
                    && isset($params['convention']);
            })
            ->andReturn('https://example.com/invitation?signature=test-sig-'.$i);

        $convention = Convention::factory()->create();

        // Generate random valid user data for each iteration
        $userData = [
            'first_name' => fake()->firstName(),
            'last_name' => fake()->lastName(),
            'email' => 'invite-test-'.$i.'-'.fake()->unique()->numberBetween(1000, 99999).'@example.com',
            'mobile' => fake()->phoneNumber(),
            'roles' => [fake()->randomElement(['ConventionUser', 'Owner'])],
        ];

        // Act: invoke the action
        $action = new InviteUserAction;
        $user = $action->execute($userData, $convention);

        // Property 1: Exactly one email was sent
        Mail::assertSentCount(1);

        // Property 2: The email is an instance of UserInvitation
        Mail::assertSent(UserInvitation::class);

        // Property 3: The email was sent to the correct recipient
        Mail::assertSent(UserInvitation::class, function (UserInvitation $mail) use ($userData) {
            return $mail->hasTo($userData['email']);
        });

        // Property 4: The mailable contains the correct user and convention
        Mail::assertSent(UserInvitation::class, function (UserInvitation $mail) use ($user, $convention) {
            return $mail->user->id === $user->id
                && $mail->convention->id === $convention->id;
        });

        // Property 5: The invitation URL is a signed URL (contains signature parameter)
        Mail::assertSent(UserInvitation::class, function (UserInvitation $mail) {
            return str_contains($mail->invitationUrl, 'signature');
        });

        // Cleanup for next iteration
        $convention->delete();
        $user->delete();

        // Reset Mockery for next iteration
        Mockery::close();
    }
})->group('property', 'email');
