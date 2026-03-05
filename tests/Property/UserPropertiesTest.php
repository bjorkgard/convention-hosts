<?php

use App\Actions\InviteUserAction;
use App\Models\Convention;
use App\Models\User;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\URL;

/**
 * Property 14: User Deduplication by Email
 *
 * For any existing user being added to a new convention with the same email address,
 * the system should connect the existing user record instead of creating a duplicate.
 *
 * Validates: Requirements 4.3
 */
it('connects existing user instead of creating duplicate when email exists', function () {
    Mail::fake();
    
    // Mock the URL generation to avoid route not found error
    URL::shouldReceive('temporarySignedRoute')
        ->andReturn('https://example.com/invitation/test');

    // Run 50 iterations to test the property across different scenarios
    for ($i = 0; $i < 50; $i++) {
        // Arrange: Create an existing user and two conventions
        $existingUser = User::factory()->create([
            'email' => 'user'.$i.'@example.com',
        ]);
        $convention1 = Convention::factory()->create();
        $convention2 = Convention::factory()->create();

        // Get initial user count
        $initialUserCount = User::count();

        // Act: Invite the same email to convention1
        $action = new InviteUserAction;
        $userData1 = [
            'first_name' => $existingUser->first_name,
            'last_name' => $existingUser->last_name,
            'email' => $existingUser->email,
            'mobile' => $existingUser->mobile,
            'roles' => ['ConventionUser'],
        ];
        $invitedUser1 = $action->execute($userData1, $convention1);

        // Assert: No new user was created
        expect(User::count())->toBe($initialUserCount)
            ->and($invitedUser1->id)->toBe($existingUser->id)
            ->and($invitedUser1->email)->toBe($existingUser->email);

        // Act: Invite the same email to convention2
        $userData2 = [
            'first_name' => fake()->firstName(),
            'last_name' => fake()->lastName(),
            'email' => $existingUser->email, // Same email
            'mobile' => fake()->phoneNumber(),
            'roles' => ['FloorUser'],
            'floor_ids' => [],
        ];
        $invitedUser2 = $action->execute($userData2, $convention2);

        // Assert: Still no new user was created, same user connected to both conventions
        expect(User::count())->toBe($initialUserCount)
            ->and($invitedUser2->id)->toBe($existingUser->id)
            ->and($invitedUser2->id)->toBe($invitedUser1->id);

        // Verify user is connected to both conventions
        $convention1->load('users');
        $convention2->load('users');
        expect($convention1->users->contains($existingUser->id))->toBeTrue()
            ->and($convention2->users->contains($existingUser->id))->toBeTrue();

        // Verify user has different roles in each convention
        expect($existingUser->rolesForConvention($convention1)->contains('ConventionUser'))->toBeTrue()
            ->and($existingUser->rolesForConvention($convention2)->contains('FloorUser'))->toBeTrue();

        // Cleanup for next iteration
        $convention1->delete();
        $convention2->delete();
        $existingUser->delete();
    }
})->group('property', 'user');

