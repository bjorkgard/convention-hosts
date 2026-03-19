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

it('assigns creator as Owner and Administrator', function () {
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
        ->and($roles)->toContain('Administrator')
        ->and($convention->users->contains($user))->toBeTrue();
});

it('rejects convention creation with missing required fields', function () {
    $user = User::factory()->create();
    $action = new CreateConventionAction;

    expect(fn () => $action->execute([
        'name' => 'Incomplete Convention',
        // missing city, country, start_date, end_date
    ], $user))->toThrow(\Exception::class);
});

it('rejects convention with end_date before start_date', function () {
    $user = User::factory()->create();

    $this->actingAs($user)->post(route('conventions.store'), [
        'name' => 'Bad Dates Convention',
        'city' => 'Berlin',
        'country' => 'Germany',
        'start_date' => now()->addWeeks(2)->toDateString(),
        'end_date' => now()->addWeek()->toDateString(),
    ])->assertSessionHasErrors('end_date');
});

it('detects overlapping conventions in same city and country', function () {
    $user = User::factory()->create();
    $startDate = now()->addMonth()->toDateString();
    $endDate = now()->addMonth()->addDays(5)->toDateString();

    Convention::factory()->create([
        'city' => 'Berlin',
        'country' => 'Germany',
        'start_date' => $startDate,
        'end_date' => $endDate,
    ]);

    $this->actingAs($user)->post(route('conventions.store'), [
        'name' => 'Overlapping Convention',
        'city' => 'Berlin',
        'country' => 'Germany',
        'start_date' => $startDate,
        'end_date' => $endDate,
    ])->assertSessionHasErrors();
});

it('allows conventions in different cities', function () {
    $user = User::factory()->create();
    $action = new CreateConventionAction;
    $startDate = now()->addMonth()->toDateString();
    $endDate = now()->addMonth()->addDays(5)->toDateString();

    $convention1 = $action->execute([
        'name' => 'Convention Berlin',
        'city' => 'Berlin',
        'country' => 'Germany',
        'start_date' => $startDate,
        'end_date' => $endDate,
    ], $user);

    $convention2 = $action->execute([
        'name' => 'Convention Munich',
        'city' => 'Munich',
        'country' => 'Germany',
        'start_date' => $startDate,
        'end_date' => $endDate,
    ], $user);

    expect($convention1)->toBeInstanceOf(Convention::class)
        ->and($convention2)->toBeInstanceOf(Convention::class)
        ->and($convention1->id)->not->toBe($convention2->id);
});
