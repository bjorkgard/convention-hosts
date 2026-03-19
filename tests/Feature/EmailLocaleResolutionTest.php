<?php

use App\Mail\EmailConfirmation;
use App\Mail\GuestConventionVerification;
use App\Mail\UserInvitation;
use App\Models\Convention;
use App\Models\User;
use Illuminate\Support\Facades\App;

it('renders UserInvitation in the user locale when set to en', function () {
    $user = User::factory()->create(['locale' => 'en']);
    $convention = Convention::factory()->create();

    $mailable = new UserInvitation($user, $convention, 'https://example.com/invite?signature=test');
    $mailable->assertHasSubject('Invitation to '.$convention->name);
    expect(App::getLocale())->toBe('en');
});

it('renders UserInvitation in Swedish when user locale is null', function () {
    $user = User::factory()->create(['locale' => null]);
    $convention = Convention::factory()->create();

    $mailable = new UserInvitation($user, $convention, 'https://example.com/invite?signature=test');
    $mailable->assertHasSubject('Inbjudan till '.$convention->name);
    expect(App::getLocale())->toBe('sv');
});

it('renders EmailConfirmation in the user locale when set to en', function () {
    $user = User::factory()->create(['locale' => 'en']);

    $mailable = new EmailConfirmation($user, 'https://example.com/confirm?signature=test');
    $mailable->assertHasSubject('Confirm Your Email Address');
    expect(App::getLocale())->toBe('en');
});

it('renders EmailConfirmation in Swedish when user locale is null', function () {
    $user = User::factory()->create(['locale' => null]);

    $mailable = new EmailConfirmation($user, 'https://example.com/confirm?signature=test');
    $mailable->assertHasSubject('Bekräfta din e-postadress');
    expect(App::getLocale())->toBe('sv');
});

it('renders GuestConventionVerification in the sending user locale when set to en', function () {
    $user = User::factory()->create(['locale' => 'en']);
    $convention = Convention::factory()->create();

    $mailable = new GuestConventionVerification($user, $convention, 'https://example.com/verify?signature=test');
    $mailable->assertHasSubject('Verify your email for '.$convention->name);
    expect(App::getLocale())->toBe('en');
});

it('renders GuestConventionVerification in Swedish when user locale is null', function () {
    $user = User::factory()->create(['locale' => null]);
    $convention = Convention::factory()->create();

    $mailable = new GuestConventionVerification($user, $convention, 'https://example.com/verify?signature=test');
    $mailable->assertHasSubject('Verifiera din e-post för '.$convention->name);
    expect(App::getLocale())->toBe('sv');
});
