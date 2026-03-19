<?php

use App\Actions\InviteUserAction;
use App\Mail\UserInvitation;
use App\Models\User;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\URL;
use Tests\Helpers\ConventionTestHelper;

it('invites a new user and creates their record', function () {
    Mail::fake();

    $structure = ConventionTestHelper::createConventionWithStructure();
    $convention = $structure['convention'];
    $action = new InviteUserAction;

    $user = $action->execute([
        'first_name' => 'Jane',
        'last_name' => 'Doe',
        'email' => 'jane@example.com',
        'mobile' => '+1234567890',
        'roles' => ['Administrator'],
    ], $convention);

    expect($user)->toBeInstanceOf(User::class)
        ->and($user->first_name)->toBe('Jane')
        ->and($user->email)->toBe('jane@example.com')
        ->and($user->email_confirmed)->toBeFalse()
        ->and($user->password)->toBeNull();
});

it('connects existing user to convention instead of creating duplicate', function () {
    Mail::fake();

    $existingUser = User::factory()->create(['email' => 'existing@example.com']);
    $structure = ConventionTestHelper::createConventionWithStructure();
    $convention = $structure['convention'];
    $action = new InviteUserAction;

    $user = $action->execute([
        'first_name' => 'Existing',
        'last_name' => 'User',
        'email' => 'existing@example.com',
        'mobile' => '+1234567890',
        'roles' => ['Administrator'],
    ], $convention);

    expect($user->id)->toBe($existingUser->id)
        ->and(User::where('email', 'existing@example.com')->count())->toBe(1);
});

it('sends invitation email via Mailgun', function () {
    Mail::fake();

    $structure = ConventionTestHelper::createConventionWithStructure();
    $convention = $structure['convention'];
    $action = new InviteUserAction;

    $action->execute([
        'first_name' => 'Email',
        'last_name' => 'Test',
        'email' => 'emailtest@example.com',
        'mobile' => '+1234567890',
        'roles' => ['Administrator'],
    ], $convention);

    Mail::assertSent(UserInvitation::class, function ($mail) {
        return $mail->hasTo('emailtest@example.com');
    });
});

it('generates a signed invitation URL', function () {
    Mail::fake();

    $structure = ConventionTestHelper::createConventionWithStructure();
    $convention = $structure['convention'];
    $action = new InviteUserAction;

    $user = $action->execute([
        'first_name' => 'Signed',
        'last_name' => 'URL',
        'email' => 'signed@example.com',
        'mobile' => '+1234567890',
        'roles' => ['Administrator'],
    ], $convention);

    Mail::assertSent(UserInvitation::class, function ($mail) use ($user) {
        return $mail->hasTo($user->email);
    });
});

it('sets password and confirms email via invitation', function () {
    $structure = ConventionTestHelper::createConventionWithStructure();
    $convention = $structure['convention'];
    $user = User::factory()->create([
        'password' => null,
        'email_confirmed' => false,
    ]);

    // Generate a valid signed URL
    $url = URL::temporarySignedRoute(
        'invitation.store',
        now()->addHours(24),
        ['user' => $user->id, 'convention' => $convention->id]
    );

    $this->post($url, [
        'password' => 'SecureP@ss1',
        'password_confirmation' => 'SecureP@ss1',
    ])->assertRedirect(route('login'));

    $user->refresh();
    expect($user->email_confirmed)->toBeTrue()
        ->and($user->password)->not->toBeNull();
});
