<?php

use App\Models\AttendancePeriod;
use App\Models\AttendanceReport;
use App\Models\Convention;
use App\Models\Floor;
use App\Models\Section;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\URL;
use Tests\Helpers\ConventionTestHelper;

/*
|--------------------------------------------------------------------------
| 1. Convention Creation to Deletion Flow
|--------------------------------------------------------------------------
*/

describe('Convention creation to deletion flow', function () {
    it('creates a convention and assigns owner role, then updates, adds structure, and deletes with cascade', function () {
        $owner = User::factory()->create();

        // Step 1: Create convention
        $conventionData = [
            'name' => 'Tech Summit 2025',
            'city' => 'Berlin',
            'country' => 'Germany',
            'address' => '123 Convention St',
            'start_date' => now()->addMonth()->toDateString(),
            'end_date' => now()->addMonth()->addDays(3)->toDateString(),
            'other_info' => 'Annual tech summit',
        ];

        $response = $this->actingAs($owner)->post(route('conventions.store'), $conventionData);

        $convention = Convention::where('name', 'Tech Summit 2025')->first();
        expect($convention)->not->toBeNull();
        $response->assertRedirect(route('conventions.show', $convention));

        // Step 2: Verify owner has Owner + ConventionUser roles
        $roles = DB::table('convention_user_roles')
            ->where('convention_id', $convention->id)
            ->where('user_id', $owner->id)
            ->pluck('role')
            ->toArray();

        expect($roles)->toContain('Owner')
            ->toContain('ConventionUser');

        // Step 3: Update convention
        $this->actingAs($owner)->put(route('conventions.update', $convention), [
            'name' => 'Tech Summit 2025 Updated',
            'city' => 'Berlin',
            'country' => 'Germany',
            'start_date' => $convention->start_date->toDateString(),
            'end_date' => $convention->end_date->toDateString(),
        ])->assertRedirect(route('conventions.show', $convention));

        expect($convention->fresh()->name)->toBe('Tech Summit 2025 Updated');

        // Step 4: Add floors and sections
        $this->actingAs($owner)->post(route('floors.store', $convention), [
            'name' => 'Ground Floor',
        ])->assertRedirect(route('conventions.show', $convention));

        $floor = Floor::where('convention_id', $convention->id)->first();
        expect($floor)->not->toBeNull();

        $this->actingAs($owner)->post(route('sections.store', [$convention, $floor]), [
            'name' => 'Section A',
            'number_of_seats' => 200,
            'elder_friendly' => true,
            'handicap_friendly' => false,
        ])->assertRedirect(route('floors.index', $convention));

        $section = Section::where('floor_id', $floor->id)->first();
        expect($section)->not->toBeNull()
            ->and($section->number_of_seats)->toBe(200);

        // Step 5: Non-owner cannot delete
        $nonOwner = User::factory()->create();
        ConventionTestHelper::attachUserToConvention($nonOwner, $convention, ['ConventionUser']);

        $this->actingAs($nonOwner)
            ->delete(route('conventions.destroy', $convention))
            ->assertForbidden();

        // Step 6: Owner deletes convention — verify cascade
        $this->actingAs($owner)
            ->delete(route('conventions.destroy', $convention))
            ->assertRedirect(route('conventions.index'));

        expect(Convention::find($convention->id))->toBeNull();
        expect(Floor::where('convention_id', $convention->id)->count())->toBe(0);
        expect(Section::where('floor_id', $floor->id)->count())->toBe(0);
    });

    it('rejects convention creation with missing required fields', function () {
        $owner = User::factory()->create();

        $this->actingAs($owner)->post(route('conventions.store'), [
            'name' => 'Incomplete Convention',
            // missing city, country, start_date, end_date
        ])->assertSessionHasErrors(['city', 'country', 'start_date', 'end_date']);
    });
});

/*
|--------------------------------------------------------------------------
| 2. User Invitation to Login Flow
|--------------------------------------------------------------------------
*/

describe('User invitation to login flow', function () {
    it('invites a new user, sets password via signed URL, confirms email, and logs in', function () {
        Mail::fake();

        $structure = ConventionTestHelper::createConventionWithStructure();
        $convention = $structure['convention'];
        $owner = $structure['owner'];

        // Step 1: Invite a new user
        $this->actingAs($owner)->post(route('users.store', $convention), [
            'first_name' => 'Jane',
            'last_name' => 'Doe',
            'email' => 'jane.doe@example.com',
            'mobile' => '+1234567890',
            'roles' => ['ConventionUser'],
        ]);

        // Step 2: Verify user was created
        $invitedUser = User::where('email', 'jane.doe@example.com')->first();
        expect($invitedUser)->not->toBeNull()
            ->and($invitedUser->email_confirmed)->toBeFalse();

        // Step 3: Verify email was sent
        Mail::assertSent(\App\Mail\UserInvitation::class, function ($mail) {
            return $mail->hasTo('jane.doe@example.com');
        });

        // Step 4: Access signed invitation URL
        $signedUrl = URL::temporarySignedRoute(
            'invitation.show',
            now()->addHours(24),
            ['user' => $invitedUser->id, 'convention' => $convention->id]
        );

        // Logout the owner first so we can test the invitation flow independently
        auth()->logout();

        $this->get($signedUrl)->assertOk();

        // Step 5: Set password via invitation
        $this->post(route('invitation.store', ['user' => $invitedUser->id, 'convention' => $convention->id]), [
            'password' => 'SecureP@ss1',
            'password_confirmation' => 'SecureP@ss1',
        ])->assertRedirect(route('login'));

        // Step 6: Verify email_confirmed is now true
        expect($invitedUser->fresh()->email_confirmed)->toBeTrue();

        // Step 7: Login with new credentials
        $this->post(route('login'), [
            'email' => 'jane.doe@example.com',
            'password' => 'SecureP@ss1',
        ])->assertRedirect();

        $this->assertAuthenticated();

        // Verify the authenticated user is the invited user
        expect(auth()->id())->toBe($invitedUser->id);
    });

    it('connects existing user to new convention via InviteUserAction instead of creating duplicate', function () {
        Mail::fake();

        $existingUser = User::factory()->create(['email' => 'existing@example.com']);

        $structure = ConventionTestHelper::createConventionWithStructure();
        $convention = $structure['convention'];

        $userCountBefore = User::count();

        // Use the action directly since StoreUserRequest enforces unique email
        // The InviteUserAction handles deduplication by finding existing users
        $action = new \App\Actions\InviteUserAction;
        $result = $action->execute([
            'first_name' => $existingUser->first_name,
            'last_name' => $existingUser->last_name,
            'email' => 'existing@example.com',
            'mobile' => '+9876543210',
            'roles' => ['ConventionUser'],
        ], $convention);

        // No new user created — existing user connected
        expect(User::count())->toBe($userCountBefore);
        expect($result->id)->toBe($existingUser->id);

        // Verify user is attached to convention
        $isAttached = DB::table('convention_user')
            ->where('convention_id', $convention->id)
            ->where('user_id', $existingUser->id)
            ->exists();

        expect($isAttached)->toBeTrue();

        // Verify role was assigned
        $hasRole = DB::table('convention_user_roles')
            ->where('convention_id', $convention->id)
            ->where('user_id', $existingUser->id)
            ->where('role', 'ConventionUser')
            ->exists();

        expect($hasRole)->toBeTrue();
    });
});

/*
|--------------------------------------------------------------------------
| 3. Occupancy Tracking Flow
|--------------------------------------------------------------------------
*/

describe('Occupancy tracking flow', function () {
    it('updates occupancy via dropdown, FULL button, and available seats with metadata', function () {
        $structure = ConventionTestHelper::createConventionWithStructure([
            'floors' => 1,
            'sections_per_floor' => 1,
        ]);
        $owner = $structure['owner'];
        $section = $structure['sections']->first();

        // Step 1: Update via dropdown (occupancy percentage)
        $this->actingAs($owner)->patch(route('sections.updateOccupancy', $section), [
            'occupancy' => 50,
        ])->assertRedirect();

        $section->refresh();
        expect($section->occupancy)->toBe(50)
            ->and($section->last_occupancy_updated_by)->toBe($owner->id)
            ->and($section->last_occupancy_updated_at)->not->toBeNull();

        // Step 2: Use FULL button — sets to 100%
        $this->actingAs($owner)->post(route('sections.setFull', $section))
            ->assertRedirect();

        $section->refresh();
        expect($section->occupancy)->toBe(100)
            ->and($section->available_seats)->toBe(0);

        // Step 3: Update via available seats
        $totalSeats = $section->number_of_seats;
        $availableSeats = (int) round($totalSeats * 0.25); // ~25% available → ~75% occupancy

        $this->actingAs($owner)->patch(route('sections.updateOccupancy', $section), [
            'available_seats' => $availableSeats,
        ])->assertRedirect();

        $section->refresh();
        // Mirror the action's snap-to-nearest-dropdown logic (not simple rounding)
        $rawOccupancy = max(0, min(100, 100 - (($availableSeats / $totalSeats) * 100)));
        $snapOptions = [0, 10, 25, 50, 75, 100];
        $expectedOccupancy = $snapOptions[0];
        $minDiff = abs($rawOccupancy - $snapOptions[0]);
        foreach ($snapOptions as $option) {
            $diff = abs($rawOccupancy - $option);
            if ($diff < $minDiff) {
                $minDiff = $diff;
                $expectedOccupancy = $option;
            }
        }
        expect($section->occupancy)->toBe($expectedOccupancy)
            ->and($section->available_seats)->toBe($availableSeats)
            ->and($section->last_occupancy_updated_by)->toBe($owner->id);
    });

    it('rejects invalid occupancy values', function () {
        $structure = ConventionTestHelper::createConventionWithStructure([
            'floors' => 1,
            'sections_per_floor' => 1,
        ]);
        $owner = $structure['owner'];
        $section = $structure['sections']->first();

        // Invalid occupancy value (not in allowed enum)
        $this->actingAs($owner)->patch(route('sections.updateOccupancy', $section), [
            'occupancy' => 33,
        ])->assertSessionHasErrors('occupancy');

        // Neither occupancy nor available_seats provided
        $this->actingAs($owner)->patch(route('sections.updateOccupancy', $section), [])
            ->assertSessionHasErrors('occupancy');
    });
});

/*
|--------------------------------------------------------------------------
| 4. Attendance Reporting Flow
|--------------------------------------------------------------------------
*/

describe('Attendance reporting flow', function () {
    it('starts report, collects attendance, stops and locks period', function () {
        $structure = ConventionTestHelper::createConventionWithStructure([
            'floors' => 1,
            'sections_per_floor' => 2,
        ]);
        $convention = $structure['convention'];
        $owner = $structure['owner'];
        $sections = $structure['sections'];

        // Step 1: Start attendance report
        $this->actingAs($owner)
            ->post(route('attendance.start', $convention))
            ->assertRedirect();

        $period = AttendancePeriod::where('convention_id', $convention->id)
            ->where('locked', false)
            ->first();

        expect($period)->not->toBeNull()
            ->and($period->locked)->toBeFalse();

        // Step 2: Report attendance for each section
        foreach ($sections as $index => $section) {
            $attendance = ($index + 1) * 50;

            $this->actingAs($owner)->post(
                route('attendance.report', [$section, $period]),
                [
                    'attendance' => $attendance,
                    'period_id' => $period->id,
                ]
            )->assertRedirect();
        }

        // Verify reports were created
        $reportCount = AttendanceReport::where('attendance_period_id', $period->id)->count();
        expect($reportCount)->toBe(2);

        // Step 3: Stop (lock) the attendance report
        $this->actingAs($owner)
            ->post(route('attendance.stop', [$convention, $period]))
            ->assertRedirect();

        expect($period->fresh()->locked)->toBeTrue();

        // Step 4: Verify locked period is immutable — cannot report after lock
        $this->actingAs($owner)->post(
            route('attendance.report', [$sections->first(), $period]),
            [
                'attendance' => 999,
                'period_id' => $period->id,
            ]
        );

        // The attendance value should NOT have changed to 999
        $report = AttendanceReport::where('attendance_period_id', $period->id)
            ->where('section_id', $sections->first()->id)
            ->first();

        expect($report->attendance)->not->toBe(999);
    });

    it('enforces max 2 reports per day limit', function () {
        $structure = ConventionTestHelper::createConventionWithStructure();
        $convention = $structure['convention'];
        $owner = $structure['owner'];

        // Create 2 periods for today (morning + afternoon)
        AttendancePeriod::create([
            'convention_id' => $convention->id,
            'date' => now()->toDateString(),
            'period' => 'morning',
            'locked' => true,
        ]);
        AttendancePeriod::create([
            'convention_id' => $convention->id,
            'date' => now()->toDateString(),
            'period' => 'afternoon',
            'locked' => true,
        ]);

        // Third attempt should fail
        $this->actingAs($owner)
            ->post(route('attendance.start', $convention))
            ->assertSessionHasErrors('attendance');
    });

    it('restricts attendance updates to original reporter only', function () {
        $structure = ConventionTestHelper::createConventionWithStructure([
            'floors' => 1,
            'sections_per_floor' => 1,
        ]);
        $convention = $structure['convention'];
        $owner = $structure['owner'];
        $section = $structure['sections']->first();

        // Start report
        $this->actingAs($owner)->post(route('attendance.start', $convention));

        $period = AttendancePeriod::where('convention_id', $convention->id)
            ->where('locked', false)
            ->first();

        // Owner reports attendance
        $this->actingAs($owner)->post(
            route('attendance.report', [$section, $period]),
            ['attendance' => 100, 'period_id' => $period->id]
        )->assertRedirect();

        // Different user tries to update the same section
        $otherUser = User::factory()->create();
        ConventionTestHelper::attachUserToConvention($otherUser, $convention, ['ConventionUser']);

        $this->actingAs($otherUser)->post(
            route('attendance.report', [$section, $period]),
            ['attendance' => 200, 'period_id' => $period->id]
        );

        // Original value should remain
        $report = AttendanceReport::where('attendance_period_id', $period->id)
            ->where('section_id', $section->id)
            ->first();

        expect($report->attendance)->toBe(100);
    });
});

/*
|--------------------------------------------------------------------------
| 5. Search and Navigation Flow
|--------------------------------------------------------------------------
*/

describe('Search and navigation flow', function () {
    it('searches sections with filters and verifies occupancy threshold and sort order', function () {
        $structure = ConventionTestHelper::createConventionWithStructure([
            'floors' => 1,
            'sections_per_floor' => 0,
            'with_owner' => true,
        ]);
        $convention = $structure['convention'];
        $owner = $structure['owner'];
        $floor = $structure['floors']->first();

        // Create sections with varying occupancy
        Section::factory()->create([
            'floor_id' => $floor->id,
            'name' => 'Low Occupancy',
            'occupancy' => 10,
            'number_of_seats' => 100,
            'elder_friendly' => true,
            'handicap_friendly' => false,
        ]);
        Section::factory()->create([
            'floor_id' => $floor->id,
            'name' => 'Mid Occupancy',
            'occupancy' => 50,
            'number_of_seats' => 100,
            'elder_friendly' => false,
            'handicap_friendly' => true,
        ]);
        Section::factory()->create([
            'floor_id' => $floor->id,
            'name' => 'High Occupancy',
            'occupancy' => 95,
            'number_of_seats' => 100,
            'elder_friendly' => false,
            'handicap_friendly' => false,
        ]);

        // Step 1: Search with no filters — should exclude occupancy >= 90%
        $response = $this->actingAs($owner)
            ->get(route('search.index', $convention))
            ->assertOk();

        $sections = $response->original->getData()['page']['props']['sections'];
        $sectionData = collect($sections['data']);

        // High occupancy (95%) should be excluded
        expect($sectionData->pluck('name')->toArray())->not->toContain('High Occupancy');
        expect($sectionData->count())->toBe(2);

        // Step 2: Verify sort order (ascending by occupancy)
        $occupancies = $sectionData->pluck('occupancy')->toArray();
        expect($occupancies)->toBe([10, 50]);

        // Step 3: Search with elder_friendly filter
        $response = $this->actingAs($owner)
            ->get(route('search.index', ['convention' => $convention, 'elder_friendly' => true]))
            ->assertOk();

        $sections = $response->original->getData()['page']['props']['sections'];
        $sectionData = collect($sections['data']);
        expect($sectionData->count())->toBe(1)
            ->and($sectionData->first()['name'])->toBe('Low Occupancy');

        // Step 4: Search with handicap_friendly filter
        $response = $this->actingAs($owner)
            ->get(route('search.index', ['convention' => $convention, 'handicap_friendly' => true]))
            ->assertOk();

        $sections = $response->original->getData()['page']['props']['sections'];
        $sectionData = collect($sections['data']);
        expect($sectionData->count())->toBe(1)
            ->and($sectionData->first()['name'])->toBe('Mid Occupancy');

        // Step 5: Search with floor filter
        $response = $this->actingAs($owner)
            ->get(route('search.index', ['convention' => $convention, 'floor_id' => $floor->id]))
            ->assertOk();

        $sections = $response->original->getData()['page']['props']['sections'];
        expect(collect($sections['data'])->count())->toBe(2);
    });

    it('allows any authenticated user to search regardless of role', function () {
        $structure = ConventionTestHelper::createConventionWithStructure([
            'floors' => 1,
            'sections_per_floor' => 1,
        ]);
        $convention = $structure['convention'];
        $section = $structure['sections']->first();

        // Create a SectionUser with minimal access
        $sectionUser = ConventionTestHelper::createUserWithRole(
            $convention,
            'SectionUser',
            ['section_ids' => [$section->id]]
        );

        $this->actingAs($sectionUser)
            ->get(route('search.index', $convention))
            ->assertOk();
    });
});

/*
|--------------------------------------------------------------------------
| 6. Export Flow
|--------------------------------------------------------------------------
*/

describe('Export flow', function () {
    beforeEach(function () {
        $dir = storage_path('app/private/exports');
        if (! is_dir($dir)) {
            mkdir($dir, 0755, true);
        }
    });

    afterEach(function () {
        $dir = storage_path('app/private/exports');
        if (is_dir($dir)) {
            foreach (glob("$dir/*") as $file) {
                @unlink($file);
            }
        }
    });

    it('allows owner to export and denies non-owner', function () {
        $structure = ConventionTestHelper::createConventionWithStructure();
        $convention = $structure['convention'];
        $owner = $structure['owner'];

        // Owner can access export — returns a file download
        $response = $this->actingAs($owner)
            ->get(route('conventions.export', ['convention' => $convention, 'format' => 'md']));

        expect($response->getStatusCode())->toBe(200);

        // Non-owner (ConventionUser) cannot export
        $conventionUser = User::factory()->create();
        ConventionTestHelper::attachUserToConvention($conventionUser, $convention, ['ConventionUser']);

        $this->actingAs($conventionUser)
            ->get(route('conventions.export', ['convention' => $convention, 'format' => 'md']))
            ->assertForbidden();
    });

    it('denies export to users with no convention access', function () {
        $structure = ConventionTestHelper::createConventionWithStructure();
        $convention = $structure['convention'];

        $outsider = User::factory()->create();

        $this->actingAs($outsider)
            ->get(route('conventions.export', ['convention' => $convention, 'format' => 'xlsx']))
            ->assertForbidden();
    });
});

/*
|--------------------------------------------------------------------------
| 7. PWA Installation Flow
|--------------------------------------------------------------------------
*/

describe('PWA installation flow', function () {
    it('has manifest.json with correct structure', function () {
        $manifestPath = public_path('manifest.json');
        expect(file_exists($manifestPath))->toBeTrue();

        $manifest = json_decode(file_get_contents($manifestPath), true);
        expect($manifest)->toBeArray()
            ->toHaveKey('name')
            ->toHaveKey('start_url')
            ->toHaveKey('display');
    });

    it('has service worker file', function () {
        $swPath = public_path('sw.js');
        expect(file_exists($swPath))->toBeTrue();

        $content = file_get_contents($swPath);
        expect($content)->not->toBeEmpty();
    });

    it('includes PWA meta tags in HTML', function () {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get(route('conventions.index'));

        $response->assertOk();
        $content = $response->getContent();

        expect($content)->toContain('manifest.json')
            ->toContain('theme-color');
    });
});

/*
|--------------------------------------------------------------------------
| Cross-cutting: Role-based access in flows
|--------------------------------------------------------------------------
*/

describe('Role-based access across flows', function () {
    it('prevents unauthenticated access to convention routes', function () {
        $convention = Convention::factory()->create();

        $this->get(route('conventions.index'))->assertRedirect(route('login'));
        $this->get(route('conventions.show', $convention))->assertRedirect(route('login'));
    });

    it('prevents users without convention access from viewing convention', function () {
        $structure = ConventionTestHelper::createConventionWithStructure();
        $convention = $structure['convention'];

        $outsider = User::factory()->create();

        $this->actingAs($outsider)
            ->get(route('conventions.show', $convention))
            ->assertForbidden();
    });

    it('allows convention show page for all role types', function () {
        $structure = ConventionTestHelper::createConventionWithStructure([
            'floors' => 1,
            'sections_per_floor' => 1,
        ]);
        $convention = $structure['convention'];
        $owner = $structure['owner'];
        $floor = $structure['floors']->first();
        $section = $structure['sections']->first();

        // Owner
        $this->actingAs($owner)
            ->get(route('conventions.show', $convention))
            ->assertOk();

        // ConventionUser
        $cu = ConventionTestHelper::createUserWithRole($convention, 'ConventionUser');
        $this->actingAs($cu)
            ->get(route('conventions.show', $convention))
            ->assertOk();

        // FloorUser
        $fu = ConventionTestHelper::createUserWithRole($convention, 'FloorUser', [
            'floor_ids' => [$floor->id],
        ]);
        $this->actingAs($fu)
            ->get(route('conventions.show', $convention))
            ->assertOk();

        // SectionUser
        $su = ConventionTestHelper::createUserWithRole($convention, 'SectionUser', [
            'section_ids' => [$section->id],
        ]);
        $this->actingAs($su)
            ->get(route('conventions.show', $convention))
            ->assertOk();
    });

    it('prevents FloorUser from adding or deleting floors', function () {
        $structure = ConventionTestHelper::createConventionWithStructure([
            'floors' => 1,
            'sections_per_floor' => 1,
        ]);
        $convention = $structure['convention'];
        $floor = $structure['floors']->first();

        $floorUser = ConventionTestHelper::createUserWithRole($convention, 'FloorUser', [
            'floor_ids' => [$floor->id],
        ]);

        // Cannot add floors
        $this->actingAs($floorUser)
            ->post(route('floors.store', $convention), ['name' => 'New Floor'])
            ->assertForbidden();

        // Cannot delete floors
        $this->actingAs($floorUser)
            ->delete(route('floors.destroy', $floor))
            ->assertForbidden();
    });

    it('prevents non-Owner/ConventionUser from starting attendance reports', function () {
        $structure = ConventionTestHelper::createConventionWithStructure([
            'floors' => 1,
            'sections_per_floor' => 1,
        ]);
        $convention = $structure['convention'];
        $floor = $structure['floors']->first();

        $floorUser = ConventionTestHelper::createUserWithRole($convention, 'FloorUser', [
            'floor_ids' => [$floor->id],
        ]);

        $this->actingAs($floorUser)
            ->post(route('attendance.start', $convention))
            ->assertForbidden();
    });
});
