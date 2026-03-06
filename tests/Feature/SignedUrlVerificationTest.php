<?php

use App\Models\Convention;
use App\Models\User;
use Illuminate\Support\Facades\URL;

use function Pest\Laravel\get;

/**
 * Signed URL Verification for Invitation Routes
 *
 * Validates: Requirements 21.5
 */
beforeEach(function () {
    $this->user = User::factory()->create([
        'email_confirmed' => false,
        'password' => null,
    ]);

    $this->convention = Convention::factory()->create();
    $this->convention->users()->attach($this->user->id);
});

it('allows access with a valid signed URL', function () {
    $url = URL::signedRoute('invitation.show', [
        'user' => $this->user->id,
        'convention' => $this->convention->id,
    ]);

    $parsed = parse_url($url);
    $getUrl = $parsed['path'].'?'.($parsed['query'] ?? '');

    $response = get($getUrl);

    $response->assertOk();
    $response->assertInertia(fn ($page) => $page->component('auth/invitation'));
});

it('rejects access with a tampered signature', function () {
    $url = URL::signedRoute('invitation.show', [
        'user' => $this->user->id,
        'convention' => $this->convention->id,
    ]);

    // Tamper with the signature
    $tamperedUrl = preg_replace('/signature=[^&]+/', 'signature=tampered-value', $url);
    $parsed = parse_url($tamperedUrl);
    $getUrl = $parsed['path'].'?'.($parsed['query'] ?? '');

    $response = get($getUrl);

    $response->assertStatus(403);
    $response->assertInertia(fn ($page) => $page
        ->component('auth/invitation-invalid')
        ->where('reason', 'expired')
    );
});

it('rejects access without a signature parameter', function () {
    $getUrl = '/invitation/'.$this->user->id.'/'.$this->convention->id;

    $response = get($getUrl);

    $response->assertStatus(403);
    $response->assertInertia(fn ($page) => $page
        ->component('auth/invitation-invalid')
        ->where('reason', 'invalid')
    );
});

it('rejects access with an expired signed URL', function () {
    // Generate a URL that expires in 1 second
    $url = URL::temporarySignedRoute('invitation.show', now()->addSecond(), [
        'user' => $this->user->id,
        'convention' => $this->convention->id,
    ]);

    // Wait for expiration
    sleep(2);

    $parsed = parse_url($url);
    $getUrl = $parsed['path'].'?'.($parsed['query'] ?? '');

    $response = get($getUrl);

    $response->assertStatus(403);
    $response->assertInertia(fn ($page) => $page
        ->component('auth/invitation-invalid')
        ->where('reason', 'expired')
    );
});

it('renders the invitation-invalid page with correct props for invalid links', function () {
    $getUrl = '/invitation/'.$this->user->id.'/'.$this->convention->id;

    $response = get($getUrl);

    $response->assertStatus(403);
    $response->assertInertia(fn ($page) => $page
        ->component('auth/invitation-invalid')
        ->has('reason')
        ->where('reason', 'invalid')
    );
});
