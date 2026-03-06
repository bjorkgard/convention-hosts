<?php

use App\Models\Convention;
use App\Models\User;
use Illuminate\Support\Facades\DB;

use function Pest\Laravel\actingAs;

/**
 * Property 10: Email Confirmation Status Display
 *
 * For ANY user in a convention, the system SHALL display a green checkmark
 * icon when email_confirmed is true, and a warning icon when email_confirmed
 * is false. The email_confirmed status must be accurately returned in the
 * user listing response.
 *
 * **Validates: Requirements 3.6, 3.7**
 */
beforeEach(function () {
    $this->owner = User::factory()->create([
        'email_confirmed' => true,
    ]);
    $this->convention = Convention::factory()->create();

    $this->convention->users()->attach($this->owner->id);
    DB::table('convention_user_roles')->insert([
        ['convention_id' => $this->convention->id, 'user_id' => $this->owner->id, 'role' => 'Owner', 'created_at' => now()],
        ['convention_id' => $this->convention->id, 'user_id' => $this->owner->id, 'role' => 'ConventionUser', 'created_at' => now()],
    ]);
});

it('returns email_confirmed as true for confirmed users in user listing', function () {
    $confirmedUser = User::factory()->create([
        'email_confirmed' => true,
    ]);

    $this->convention->users()->attach($confirmedUser->id);
    DB::table('convention_user_roles')->insert([
        'convention_id' => $this->convention->id,
        'user_id' => $confirmedUser->id,
        'role' => 'ConventionUser',
        'created_at' => now(),
    ]);

    actingAs($this->owner);

    $response = $this->get(route('users.index', $this->convention));
    $response->assertOk();

    $response->assertInertia(function ($page) use ($confirmedUser) {
        $page->has('users');
        $users = collect($page->toArray()['props']['users']);
        $found = $users->firstWhere('id', $confirmedUser->id);

        expect($found)->not->toBeNull()
            ->and($found['email_confirmed'])->toBeTrue();
    });
})->group('property', 'email-confirmation');

it('returns email_confirmed as false for unconfirmed users in user listing', function () {
    $unconfirmedUser = User::factory()->create([
        'email_confirmed' => false,
    ]);

    $this->convention->users()->attach($unconfirmedUser->id);
    DB::table('convention_user_roles')->insert([
        'convention_id' => $this->convention->id,
        'user_id' => $unconfirmedUser->id,
        'role' => 'SectionUser',
        'created_at' => now(),
    ]);

    actingAs($this->owner);

    $response = $this->get(route('users.index', $this->convention));
    $response->assertOk();

    $response->assertInertia(function ($page) use ($unconfirmedUser) {
        $page->has('users');
        $users = collect($page->toArray()['props']['users']);
        $found = $users->firstWhere('id', $unconfirmedUser->id);

        expect($found)->not->toBeNull()
            ->and($found['email_confirmed'])->toBeFalse();
    });
})->group('property', 'email-confirmation');

it('accurately reflects email_confirmed status for mixed confirmed and unconfirmed users', function () {
    $createdUsers = [];

    for ($i = 0; $i < 10; $i++) {
        $isConfirmed = fake()->boolean();

        $user = User::factory()->create([
            'email_confirmed' => $isConfirmed,
        ]);

        $this->convention->users()->attach($user->id);
        DB::table('convention_user_roles')->insert([
            'convention_id' => $this->convention->id,
            'user_id' => $user->id,
            'role' => fake()->randomElement(['ConventionUser', 'FloorUser', 'SectionUser']),
            'created_at' => now(),
        ]);

        $createdUsers[] = ['id' => $user->id, 'expected' => $isConfirmed];
    }

    actingAs($this->owner);

    $response = $this->get(route('users.index', $this->convention));
    $response->assertOk();

    $response->assertInertia(function ($page) use ($createdUsers) {
        $users = collect($page->toArray()['props']['users']);

        foreach ($createdUsers as $idx => $expected) {
            $found = $users->firstWhere('id', $expected['id']);

            expect($found)->not->toBeNull(
                "User {$expected['id']} should be in the response"
            );

            expect((bool) $found['email_confirmed'])->toBe($expected['expected'],
                "User {$expected['id']} (iteration {$idx}): email_confirmed should be ".($expected['expected'] ? 'true' : 'false')
            );
        }
    });
})->group('property', 'email-confirmation');
