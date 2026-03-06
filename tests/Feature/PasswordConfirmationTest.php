<?php

use App\Models\Convention;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\URL;

use function Pest\Laravel\post;

/**
 * Property 8: Password Confirmation Sets Email Verified
 *
 * For any user setting their password via invitation link, the email_confirmed
 * field should be set to true, the password should be hashed (not stored in
 * plain text), and the user should be redirected to the login page.
 *
 * **Validates: Requirements 3.4**
 */
beforeEach(function () {
    $this->user = User::factory()->create([
        'email_confirmed' => false,
        'password' => null,
    ]);

    $this->convention = Convention::factory()->create();

    // Attach user to convention
    $this->convention->users()->attach($this->user->id);
    DB::table('convention_user_roles')->insert([
        'convention_id' => $this->convention->id,
        'user_id' => $this->user->id,
        'role' => 'SectionUser',
        'created_at' => now(),
    ]);
});

it('sets email_confirmed to true after setting password via invitation', function () {
    $password = 'ValidPass1@';

    $url = URL::signedRoute('invitation.store', [
        'user' => $this->user->id,
        'convention' => $this->convention->id,
    ]);

    // Extract path + query from signed URL
    $parsed = parse_url($url);
    $postUrl = $parsed['path'].'?'.($parsed['query'] ?? '');

    $response = post($postUrl, [
        'password' => $password,
        'password_confirmation' => $password,
    ]);

    $response->assertRedirect(route('login'));

    $this->user->refresh();
    expect($this->user->email_confirmed)->toBeTrue();
});

it('hashes the password and does not store it in plain text', function () {
    $password = 'SecureP@ss1';

    $url = URL::signedRoute('invitation.store', [
        'user' => $this->user->id,
        'convention' => $this->convention->id,
    ]);

    $parsed = parse_url($url);
    $postUrl = $parsed['path'].'?'.($parsed['query'] ?? '');

    post($postUrl, [
        'password' => $password,
        'password_confirmation' => $password,
    ]);

    $this->user->refresh();

    // Password should not be stored as plain text
    expect($this->user->password)->not->toBe($password);
    // Password should be verifiable via Hash::check
    expect(Hash::check($password, $this->user->password))->toBeTrue();
});

it('redirects to login after setting password', function () {
    $password = 'MyP@ssw0rd';

    $url = URL::signedRoute('invitation.store', [
        'user' => $this->user->id,
        'convention' => $this->convention->id,
    ]);

    $parsed = parse_url($url);
    $postUrl = $parsed['path'].'?'.($parsed['query'] ?? '');

    $response = post($postUrl, [
        'password' => $password,
        'password_confirmation' => $password,
    ]);

    $response->assertRedirect(route('login'));
});

it('sets email_confirmed and hashes password for randomly generated valid passwords', function () {
    $symbols = ['@', '$', '!', '%', '*', '#', '?', '&'];

    for ($i = 0; $i < 3; $i++) {
        // Create a fresh user for each iteration
        $user = User::factory()->create([
            'email_confirmed' => false,
            'password' => null,
        ]);
        $this->convention->users()->attach($user->id);
        DB::table('convention_user_roles')->insert([
            'convention_id' => $this->convention->id,
            'user_id' => $user->id,
            'role' => fake()->randomElement(['Owner', 'ConventionUser', 'FloorUser', 'SectionUser']),
            'created_at' => now(),
        ]);

        // Generate a random valid password meeting all criteria
        $lowercase = chr(rand(97, 122));
        $uppercase = chr(rand(65, 90));
        $number = (string) rand(0, 9);
        $symbol = $symbols[array_rand($symbols)];

        $extra = '';
        for ($j = 0; $j < rand(4, 12); $j++) {
            $type = rand(0, 3);
            $extra .= match ($type) {
                0 => chr(rand(97, 122)),
                1 => chr(rand(65, 90)),
                2 => (string) rand(0, 9),
                3 => $symbols[array_rand($symbols)],
            };
        }

        $password = str_shuffle($lowercase.$uppercase.$number.$symbol.$extra);

        $url = URL::signedRoute('invitation.store', [
            'user' => $user->id,
            'convention' => $this->convention->id,
        ]);

        $parsed = parse_url($url);
        $postUrl = $parsed['path'].'?'.($parsed['query'] ?? '');

        $response = post($postUrl, [
            'password' => $password,
            'password_confirmation' => $password,
        ]);

        // Property: redirect to login
        $response->assertRedirect(route('login'));

        $user->refresh();

        // Property: email_confirmed is set to true
        expect($user->email_confirmed)
            ->toBeTrue("Iteration {$i}: email_confirmed should be true after setting password");

        // Property: password is hashed, not plain text
        expect($user->password)->not->toBe($password, "Iteration {$i}: password should not be stored as plain text");
        expect(Hash::check($password, $user->password))
            ->toBeTrue("Iteration {$i}: hashed password should verify against original");
    }
});
