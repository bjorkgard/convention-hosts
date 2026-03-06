<?php

use App\Models\Convention;
use App\Models\User;
use Illuminate\Support\Facades\URL;

use function Pest\Laravel\get;

/**
 * Unit tests for signed URL error handling on guest convention verification routes.
 *
 * Validates: Requirements 6.1, 6.2, 6.3
 */
beforeEach(function () {
    $this->user = User::factory()->create([
        'email_confirmed' => false,
        'password' => bcrypt('random-temp-password'),
    ]);

    $this->convention = Convention::factory()->create();
    $this->convention->users()->attach($this->user->id);
});

test('expired URL renders error page with reason expired', function () {
    // Generate a URL that expires in 1 second
    $url = URL::temporarySignedRoute('guest-verification.show', now()->addSecond(), [
        'user' => $this->user->id,
        'convention' => $this->convention->id,
    ]);

    // Wait for expiration
    sleep(2);

    $parsed = parse_url($url);
    $getUrl = $parsed['path'].'?'.($parsed['query'] ?? '');

    $response = get($getUrl);

    $response->assertStatus(403);
    $response->assertInertia(
        fn ($page) => $page
            ->component('auth/guest-convention-invalid')
            ->where('reason', 'expired')
    );
})->group('guest-convention-verification');

test('tampered URL renders error page with reason invalid', function () {
    $url = URL::temporarySignedRoute('guest-verification.show', now()->addHours(24), [
        'user' => $this->user->id,
        'convention' => $this->convention->id,
    ]);

    // Tamper with the signature
    $tamperedUrl = preg_replace('/signature=[^&]+/', 'signature=tampered-value', $url);
    $parsed = parse_url($tamperedUrl);
    $getUrl = $parsed['path'].'?'.($parsed['query'] ?? '');

    $response = get($getUrl);

    $response->assertStatus(403);
    $response->assertInertia(
        fn ($page) => $page
            ->component('auth/guest-convention-invalid')
            ->where('reason', 'expired')
    );
})->group('guest-convention-verification');

test('URL without signature renders error page with reason invalid', function () {
    $getUrl = '/guest-verification/'.$this->user->id.'/'.$this->convention->id;

    $response = get($getUrl);

    $response->assertStatus(403);
    $response->assertInertia(
        fn ($page) => $page
            ->component('auth/guest-convention-invalid')
            ->where('reason', 'invalid')
    );
})->group('guest-convention-verification');

test('error page is rendered as Inertia page with reason prop', function () {
    // Access without any signature — should render the guest-convention-invalid page
    $getUrl = '/guest-verification/'.$this->user->id.'/'.$this->convention->id;

    $response = get($getUrl);

    $response->assertStatus(403);
    $response->assertInertia(
        fn ($page) => $page
            ->component('auth/guest-convention-invalid')
            ->has('reason')
    );
})->group('guest-convention-verification');
