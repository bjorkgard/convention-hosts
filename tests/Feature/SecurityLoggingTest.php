<?php

use App\Models\Convention;
use App\Models\User;

function getSecurityLogContent(): string
{
    $pattern = storage_path('logs/security-*.log');
    $files = glob($pattern);

    if (empty($files)) {
        return '';
    }

    // Get the most recent log file
    $latestFile = end($files);

    return file_get_contents($latestFile);
}

function clearSecurityLogs(): void
{
    $pattern = storage_path('logs/security-*.log');
    $files = glob($pattern);

    foreach ($files as $file) {
        file_put_contents($file, '');
    }
}

beforeEach(function () {
    clearSecurityLogs();
});

test('it logs failed login attempts', function () {
    $user = User::factory()->create();

    $this->post('/login', [
        'email' => $user->email,
        'password' => 'wrong-password',
    ]);

    $log = getSecurityLogContent();

    expect($log)->toContain('Failed login attempt')
        ->toContain('failed_login')
        ->toContain($user->email);
});

test('it logs authorization failure when user has no convention access', function () {
    $user = User::factory()->create([
        'password' => bcrypt('Password1!'),
        'email_verified_at' => now(),
    ]);

    $convention = Convention::factory()->create();

    $this->actingAs($user)
        ->get("/conventions/{$convention->id}")
        ->assertStatus(403);

    $log = getSecurityLogContent();

    expect($log)->toContain('Authorization failure')
        ->toContain('authorization_failure')
        ->toContain((string) $user->id);
});

test('it logs authorization failure when non-owner attempts owner action', function () {
    $user = User::factory()->create([
        'password' => bcrypt('Password1!'),
        'email_verified_at' => now(),
    ]);

    $convention = Convention::factory()->create();

    // Attach user as ConventionUser (not Owner)
    $convention->users()->attach($user->id);
    \Illuminate\Support\Facades\DB::table('convention_user_roles')->insert([
        'convention_id' => $convention->id,
        'user_id' => $user->id,
        'role' => 'ConventionUser',
        'created_at' => now(),
    ]);

    $this->actingAs($user)
        ->delete("/conventions/{$convention->id}")
        ->assertStatus(403);

    $log = getSecurityLogContent();

    expect($log)->toContain('Authorization failure')
        ->toContain('authorization_failure')
        ->toContain('Owner role required');
});

test('it logs invalid signed URL access', function () {
    $user = User::factory()->create();
    $convention = Convention::factory()->create();

    $this->get("/invitation/{$user->id}/{$convention->id}?signature=invalid")
        ->assertStatus(403);

    $log = getSecurityLogContent();

    expect($log)->toContain('Invalid signed URL access')
        ->toContain('invalid_signed_url');
});

test('security log channel is configured in logging config', function () {
    $config = config('logging.channels.security');

    expect($config)->not->toBeNull()
        ->and($config['driver'])->toBe('daily')
        ->and($config['path'])->toBe(storage_path('logs/security.log'))
        ->and($config['days'])->toBe(30);
});
