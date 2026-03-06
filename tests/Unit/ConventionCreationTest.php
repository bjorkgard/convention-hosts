<?php

use App\Actions\CreateConventionAction;
use App\Models\Convention;
use App\Models\User;
use Illuminate\Support\Facades\DB;

it('creates a convention with all required fields', function () {
    $user = User::factory()->create();
    $action = new CreateConventionAction;

    $convention = $action->execute([
        'name' => 'Test Convention',
        'city' => 'Berlin',
        'country' => 'Germany',
        'start_date' => now()->addWeek()->toDateString(),
        'end_date' => now()->addWeeks(2)->toDateString(),
    ], $user);

    expect($convention)->toBeInstanceOf(Convention::class)
        ->and($convention->name)->toBe('Test Convention')
        ->and($convention->city)->toBe('Berlin')
        ->and($convention->country)->toBe('Germany');
});

it('creates a convention with optional fields', function () {
    $user = User::factory()->create();
    $action = new CreateConventionAction;

    $convention = $action->execute([
        'name' => 'Full Convention',
        'city' => 'Munich',
        'country' => 'Germany',
        'address' => '123 Main St',
        'start_date' => now()->addWeek()->toDateString(),
        'end_date' => now()->addWeeks(2)->toDateString(),
        'other_info' => 'Some extra details',
    ], $user);

    expect($convention->address)->toBe('123 Main St')
        ->and($convention->other_info)->toBe('Some extra details');
});

it('assigns creator as Owner and ConventionUser', function () {
    $user = User::factory()->create();
    $action = new CreateConventionAction;

    $convention = $action->execute([
        'name' => 'Role Test Convention',
        'city' => 'Paris',
        'country' => 'France',
        'start_date' => now()->addWeek()->toDateString(),
        'end_date' => now()->addWeeks(2)->toDateString(),
    ], $user);

    $roles = DB::table('convention_user_roles')
        ->where('convention_id', $convention->id)
        ->where('user_id', $user->id)
        ->pluck('role')
        ->toArray();

    expect($roles)->toContain('Owner')
        ->and($roles)->toContain('ConventionUser')
        ->and($convention->users->contains($user))->toBeTrue();
});

it('rejects convention creation with missing required fields via form request', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->post(route('conventions.store'), [])
        ->assertSessionHasErrors(['name', 'city', 'country', 'start_date', 'end_date']);
});

it('rejects convention with end_date before start_date via form request', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->post(route('conventions.store'), [
            'name' => 'Bad Dates Convention',
            'city' => 'Rome',
            'country' => 'Italy',
            'start_date' => now()->addWeeks(2)->toDateString(),
            'end_date' => now()->addWeek()->toDateString(),
        ])
        ->assertSessionHasErrors('end_date');
});

it('detects overlapping conventions in the same city and country', function () {
    $user = User::factory()->create();

    // Create an existing convention
    Convention::factory()->create([
        'city' => 'London',
        'country' => 'UK',
        'start_date' => now()->addDays(10)->toDateString(),
        'end_date' => now()->addDays(20)->toDateString(),
    ]);

    // Try to create an overlapping convention
    $this->actingAs($user)
        ->post(route('conventions.store'), [
            'name' => 'Overlapping Convention',
            'city' => 'London',
            'country' => 'UK',
            'start_date' => now()->addDays(15)->toDateString(),
            'end_date' => now()->addDays(25)->toDateString(),
        ])
        ->assertSessionHasErrors('start_date');
});

it('allows conventions in different cities even with overlapping dates', function () {
    $user = User::factory()->create();

    Convention::factory()->create([
        'city' => 'London',
        'country' => 'UK',
        'start_date' => now()->addDays(10)->toDateString(),
        'end_date' => now()->addDays(20)->toDateString(),
    ]);

    $this->actingAs($user)
        ->post(route('conventions.store'), [
            'name' => 'Different City Convention',
            'city' => 'Manchester',
            'country' => 'UK',
            'start_date' => now()->addDays(15)->toDateString(),
            'end_date' => now()->addDays(25)->toDateString(),
        ])
        ->assertSessionHasNoErrors();
});
