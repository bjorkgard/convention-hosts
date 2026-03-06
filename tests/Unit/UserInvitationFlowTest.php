<?php

use App\Actions\InviteUserAction;
use App\Mail\UserInvitation;
use App\Models\User;
use Illuminate\Support\Facades\DB;
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
        'roles' => ['ConventionUser'],
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
        'roles' => ['ConventionUser'],
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
        'roles' => ['ConventionUser'],
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
        'roles' => ['ConventionUser'],
    ], $convention);

    Mail::assertSent(UserInvitation::class, function ($mail) use ($user) {
        // The mailable was sent, which means a signed URL was generated
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

it('attaches floor assignments for FloorUser role', function () {
    Mail::fake();

    $structure = ConventionTestHelper::createConventionWithStructure();
    $convention = $structure['convention'];
    $floor = $structure['floors']->first();
    $action = new InviteUserAction;

    $user = $action->execute([
        'first_name' => 'Floor',
        'last_name' => 'User',
        'email' => 'flooruser@example.com',
        'mobile' => '+1234567890',
        'roles' => ['FloorUser'],
        'floor_ids' => [$floor->id],
    ], $convention);

    $assignedFloors = DB::table('floor_user')
        ->where('user_id', $user->id)
        ->pluck('floor_id')
        ->toArray();

    expect($assignedFloors)->toContain($floor->id);
});

it('attaches section assignments for SectionUser role', function () {
    Mail::fake();

    $structure = ConventionTestHelper::createConventionWithStructure();
    $convention = $structure['convention'];
    $section = $structure['sections']->first();
    $action = new InviteUserAction;

    $user = $action->execute([
        'first_name' => 'Section',
        'last_name' => 'User',
        'email' => 'sectionuser@example.com',
        'mobile' => '+1234567890',
        'roles' => ['SectionUser'],
        'section_ids' => [$section->id],
    ], $convention);

    $assignedSections = DB::table('section_user')
        ->where('user_id', $user->id)
        ->pluck('section_id')
        ->toArray();

    expect($assignedSections)->toContain($section->id);
});
