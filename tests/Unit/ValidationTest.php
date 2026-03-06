<?php

use App\Models\User;
use Illuminate\Support\Facades\URL;
use Tests\Helpers\ConventionTestHelper;

// --- Email domain restriction ---

it('rejects emails containing jwpub.org', function () {
    $structure = ConventionTestHelper::createConventionWithStructure();
    $convention = $structure['convention'];
    $owner = $structure['owner'];

    $this->actingAs($owner)
        ->post(route('users.store', $convention), [
            'first_name' => 'Test',
            'last_name' => 'User',
            'email' => 'user@jwpub.org',
            'mobile' => '+1234567890',
            'roles' => ['ConventionUser'],
        ])
        ->assertSessionHasErrors('email');
});

it('rejects emails with jwpub.org in subdomain', function () {
    $structure = ConventionTestHelper::createConventionWithStructure();
    $convention = $structure['convention'];
    $owner = $structure['owner'];

    $this->actingAs($owner)
        ->post(route('users.store', $convention), [
            'first_name' => 'Test',
            'last_name' => 'User',
            'email' => 'user@mail.jwpub.org',
            'mobile' => '+1234567890',
            'roles' => ['ConventionUser'],
        ])
        ->assertSessionHasErrors('email');
});

it('accepts valid email addresses', function () {
    $structure = ConventionTestHelper::createConventionWithStructure();
    $convention = $structure['convention'];
    $owner = $structure['owner'];

    \Illuminate\Support\Facades\Mail::fake();

    $this->actingAs($owner)
        ->post(route('users.store', $convention), [
            'first_name' => 'Valid',
            'last_name' => 'User',
            'email' => 'valid@example.com',
            'mobile' => '+1234567890',
            'roles' => ['ConventionUser'],
        ])
        ->assertSessionHasNoErrors();
});

// --- Password criteria ---

it('rejects password shorter than 8 characters', function () {
    $structure = ConventionTestHelper::createConventionWithStructure();
    $convention = $structure['convention'];
    $user = User::factory()->create(['password' => null, 'email_confirmed' => false]);

    $url = URL::temporarySignedRoute(
        'invitation.store',
        now()->addHours(24),
        ['user' => $user->id, 'convention' => $convention->id]
    );

    $this->post($url, [
        'password' => 'Ab1@',
        'password_confirmation' => 'Ab1@',
    ])->assertSessionHasErrors('password');
});

it('rejects password without uppercase letter', function () {
    $structure = ConventionTestHelper::createConventionWithStructure();
    $convention = $structure['convention'];
    $user = User::factory()->create(['password' => null, 'email_confirmed' => false]);

    $url = URL::temporarySignedRoute(
        'invitation.store',
        now()->addHours(24),
        ['user' => $user->id, 'convention' => $convention->id]
    );

    $this->post($url, [
        'password' => 'lowercase1@pass',
        'password_confirmation' => 'lowercase1@pass',
    ])->assertSessionHasErrors('password');
});

it('rejects password without lowercase letter', function () {
    $structure = ConventionTestHelper::createConventionWithStructure();
    $convention = $structure['convention'];
    $user = User::factory()->create(['password' => null, 'email_confirmed' => false]);

    $url = URL::temporarySignedRoute(
        'invitation.store',
        now()->addHours(24),
        ['user' => $user->id, 'convention' => $convention->id]
    );

    $this->post($url, [
        'password' => 'UPPERCASE1@PASS',
        'password_confirmation' => 'UPPERCASE1@PASS',
    ])->assertSessionHasErrors('password');
});

it('rejects password without number', function () {
    $structure = ConventionTestHelper::createConventionWithStructure();
    $convention = $structure['convention'];
    $user = User::factory()->create(['password' => null, 'email_confirmed' => false]);

    $url = URL::temporarySignedRoute(
        'invitation.store',
        now()->addHours(24),
        ['user' => $user->id, 'convention' => $convention->id]
    );

    $this->post($url, [
        'password' => 'NoNumber@Pass',
        'password_confirmation' => 'NoNumber@Pass',
    ])->assertSessionHasErrors('password');
});

it('rejects password without symbol', function () {
    $structure = ConventionTestHelper::createConventionWithStructure();
    $convention = $structure['convention'];
    $user = User::factory()->create(['password' => null, 'email_confirmed' => false]);

    $url = URL::temporarySignedRoute(
        'invitation.store',
        now()->addHours(24),
        ['user' => $user->id, 'convention' => $convention->id]
    );

    $this->post($url, [
        'password' => 'NoSymbol1Pass',
        'password_confirmation' => 'NoSymbol1Pass',
    ])->assertSessionHasErrors('password');
});

it('accepts valid password meeting all criteria', function () {
    $structure = ConventionTestHelper::createConventionWithStructure();
    $convention = $structure['convention'];
    $user = User::factory()->create(['password' => null, 'email_confirmed' => false]);

    $url = URL::temporarySignedRoute(
        'invitation.store',
        now()->addHours(24),
        ['user' => $user->id, 'convention' => $convention->id]
    );

    $this->post($url, [
        'password' => 'ValidP@ss1',
        'password_confirmation' => 'ValidP@ss1',
    ])->assertSessionHasNoErrors();
});

// --- Form validation errors ---

it('returns validation errors for convention creation with missing fields', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->post(route('conventions.store'), [
            'name' => 'Only Name',
        ])
        ->assertSessionHasErrors(['city', 'country', 'start_date', 'end_date']);
});

it('returns validation errors for user creation with missing fields', function () {
    $structure = ConventionTestHelper::createConventionWithStructure();
    $convention = $structure['convention'];
    $owner = $structure['owner'];

    $this->actingAs($owner)
        ->post(route('users.store', $convention), [
            'first_name' => 'Only',
        ])
        ->assertSessionHasErrors(['last_name', 'email', 'mobile', 'roles']);
});

// --- Input preservation ---

it('preserves input when convention creation fails validation', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->from(route('conventions.create'))
        ->post(route('conventions.store'), [
            'name' => 'Preserved Name',
            'city' => 'Preserved City',
            // Missing required fields
        ])
        ->assertSessionHasErrors()
        ->assertRedirect();

    // Old input should be available
    expect(session('_old_input.name'))->toBe('Preserved Name')
        ->and(session('_old_input.city'))->toBe('Preserved City');
});

it('requires email uniqueness for user creation', function () {
    $structure = ConventionTestHelper::createConventionWithStructure();
    $convention = $structure['convention'];
    $owner = $structure['owner'];

    // Create a user with a specific email
    User::factory()->create(['email' => 'taken@example.com']);

    $this->actingAs($owner)
        ->post(route('users.store', $convention), [
            'first_name' => 'Duplicate',
            'last_name' => 'Email',
            'email' => 'taken@example.com',
            'mobile' => '+1234567890',
            'roles' => ['ConventionUser'],
        ])
        ->assertSessionHasErrors('email');
});

it('requires floor_ids when FloorUser role is assigned', function () {
    $structure = ConventionTestHelper::createConventionWithStructure();
    $convention = $structure['convention'];
    $owner = $structure['owner'];

    $this->actingAs($owner)
        ->post(route('users.store', $convention), [
            'first_name' => 'Floor',
            'last_name' => 'User',
            'email' => 'flooruser-test@example.com',
            'mobile' => '+1234567890',
            'roles' => ['FloorUser'],
            // Missing floor_ids
        ])
        ->assertSessionHasErrors('floor_ids');
});

it('requires section_ids when SectionUser role is assigned', function () {
    $structure = ConventionTestHelper::createConventionWithStructure();
    $convention = $structure['convention'];
    $owner = $structure['owner'];

    $this->actingAs($owner)
        ->post(route('users.store', $convention), [
            'first_name' => 'Section',
            'last_name' => 'User',
            'email' => 'sectionuser-test@example.com',
            'mobile' => '+1234567890',
            'roles' => ['SectionUser'],
            // Missing section_ids
        ])
        ->assertSessionHasErrors('section_ids');
});
