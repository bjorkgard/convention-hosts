<?php

use App\Models\AttendancePeriod;
use App\Models\Convention;
use App\Models\Floor;
use App\Models\Section;
use App\Models\User;
use Illuminate\Support\Facades\Mail;
use Tests\Helpers\ConventionTestHelper;

/*
|--------------------------------------------------------------------------
| Role-Based Access Control Integration Tests
|--------------------------------------------------------------------------
|
| Verifies role-based access control across ALL pages and endpoints.
| Two-tier system: Owner (full control) and Administrator (manage, no delete/export).
|
*/

beforeEach(function () {
    Mail::fake();

    $this->structure = ConventionTestHelper::createConventionWithStructure([
        'floors' => 2,
        'sections_per_floor' => 2,
    ]);

    $this->convention = $this->structure['convention'];
    $this->owner = $this->structure['owner'];
    $this->floor1 = $this->structure['floors'][0];
    $this->floor2 = $this->structure['floors'][1];
    $this->section1 = $this->structure['sections'][0];
    $this->section2 = $this->structure['sections'][1];
    $this->section3 = $this->structure['sections'][2];
    $this->section4 = $this->structure['sections'][3];

    $this->administrator = ConventionTestHelper::createUserWithRole(
        $this->convention, 'Administrator'
    );

    $this->outsider = User::factory()->create();

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

/*
|--------------------------------------------------------------------------
| 1. Owner Access — Full Control
|--------------------------------------------------------------------------
*/

describe('Owner access - full control', function () {
    it('can view convention show page with all data', function () {
        $this->actingAs($this->owner)
            ->get(route('conventions.show', $this->convention))
            ->assertOk();
    });

    it('can update convention details', function () {
        $this->actingAs($this->owner)
            ->put(route('conventions.update', $this->convention), [
                'name' => 'Updated Convention',
                'city' => $this->convention->city,
                'country' => $this->convention->country,
                'start_date' => $this->convention->start_date->toDateString(),
                'end_date' => $this->convention->end_date->toDateString(),
            ])
            ->assertRedirect(route('conventions.show', $this->convention));

        expect($this->convention->fresh()->name)->toBe('Updated Convention');
    });

    it('can delete convention', function () {
        $this->actingAs($this->owner)
            ->delete(route('conventions.destroy', $this->convention))
            ->assertRedirect(route('conventions.index'));

        expect(Convention::find($this->convention->id))->toBeNull();
    });

    it('can export convention data', function () {
        $this->actingAs($this->owner)
            ->get(route('conventions.export', ['convention' => $this->convention, 'format' => 'md']))
            ->assertOk();
    });

    it('can create floors', function () {
        $this->actingAs($this->owner)
            ->post(route('floors.store', $this->convention), ['name' => 'New Floor'])
            ->assertRedirect(route('conventions.show', $this->convention));

        expect(Floor::where('convention_id', $this->convention->id)->where('name', 'New Floor')->exists())->toBeTrue();
    });

    it('can update any floor', function () {
        $this->actingAs($this->owner)
            ->put(route('floors.update', $this->floor1), ['name' => 'Renamed Floor'])
            ->assertRedirect(route('conventions.show', $this->convention));

        expect($this->floor1->fresh()->name)->toBe('Renamed Floor');
    });

    it('can delete any floor', function () {
        $floorId = $this->floor2->id;

        $this->actingAs($this->owner)
            ->delete(route('floors.destroy', $this->floor2))
            ->assertRedirect(route('conventions.show', $this->convention));

        expect(Floor::find($floorId))->toBeNull();
    });

    it('can create sections on any floor', function () {
        $this->actingAs($this->owner)
            ->post(route('sections.store', [$this->convention, $this->floor1]), [
                'name' => 'New Section',
                'number_of_seats' => 100,
            ])
            ->assertRedirect(route('floors.index', $this->convention));
    });

    it('can update any section', function () {
        $this->actingAs($this->owner)
            ->put(route('sections.update', $this->section3), [
                'name' => 'Renamed Section',
                'number_of_seats' => 150,
            ])
            ->assertRedirect(route('floors.index', $this->convention));
    });

    it('can delete any section', function () {
        $sectionId = $this->section4->id;

        $this->actingAs($this->owner)
            ->delete(route('sections.destroy', $this->section4))
            ->assertRedirect(route('floors.index', $this->convention));

        expect(Section::find($sectionId))->toBeNull();
    });

    it('can update occupancy on any section', function () {
        $this->actingAs($this->owner)
            ->patch(route('sections.updateOccupancy', $this->section3), ['occupancy' => 75])
            ->assertRedirect();

        expect($this->section3->fresh()->occupancy)->toBe(75);
    });

    it('can set any section to full', function () {
        $this->actingAs($this->owner)
            ->post(route('sections.setFull', $this->section3))
            ->assertRedirect();

        expect($this->section3->fresh()->occupancy)->toBe(100);
    });

    it('can start and stop attendance reports', function () {
        $this->actingAs($this->owner)
            ->post(route('attendance.start', $this->convention))
            ->assertRedirect();

        $period = AttendancePeriod::where('convention_id', $this->convention->id)
            ->where('locked', false)->first();

        expect($period)->not->toBeNull();

        $this->actingAs($this->owner)
            ->post(route('attendance.stop', [$this->convention, $period]))
            ->assertRedirect();

        expect($period->fresh()->locked)->toBeTrue();
    });

    it('can access search page', function () {
        $this->actingAs($this->owner)
            ->get(route('search.index', $this->convention))
            ->assertOk();
    });

    it('can view users index', function () {
        $this->actingAs($this->owner)
            ->get(route('users.index', $this->convention))
            ->assertOk();
    });

    it('can invite users to convention', function () {
        $this->actingAs($this->owner)
            ->post(route('users.store', $this->convention), [
                'first_name' => 'New',
                'last_name' => 'User',
                'email' => 'newuser-owner-test@example.com',
                'mobile' => '+1234567890',
                'roles' => ['Administrator'],
            ])
            ->assertRedirect();

        expect(User::where('email', 'newuser-owner-test@example.com')->exists())->toBeTrue();
    });

    it('can view floors index', function () {
        $this->actingAs($this->owner)
            ->get(route('floors.index', $this->convention))
            ->assertOk();
    });
});

/*
|--------------------------------------------------------------------------
| 2. Administrator Access — Convention-wide, no delete/export
|--------------------------------------------------------------------------
*/

describe('Administrator access - convention-wide read/write', function () {
    it('can view convention show page', function () {
        $this->actingAs($this->administrator)
            ->get(route('conventions.show', $this->convention))
            ->assertOk();
    });

    it('can update convention details', function () {
        $this->actingAs($this->administrator)
            ->put(route('conventions.update', $this->convention), [
                'name' => 'Admin Updated',
                'city' => $this->convention->city,
                'country' => $this->convention->country,
                'start_date' => $this->convention->start_date->toDateString(),
                'end_date' => $this->convention->end_date->toDateString(),
            ])
            ->assertRedirect(route('conventions.show', $this->convention));
    });

    it('CANNOT delete convention', function () {
        $this->actingAs($this->administrator)
            ->delete(route('conventions.destroy', $this->convention))
            ->assertForbidden();
    });

    it('CANNOT export convention data', function () {
        $this->actingAs($this->administrator)
            ->get(route('conventions.export', ['convention' => $this->convention, 'format' => 'md']))
            ->assertForbidden();
    });

    it('can create floors', function () {
        $this->actingAs($this->administrator)
            ->post(route('floors.store', $this->convention), ['name' => 'Admin Floor'])
            ->assertRedirect(route('conventions.show', $this->convention));
    });

    it('can update any floor', function () {
        $this->actingAs($this->administrator)
            ->put(route('floors.update', $this->floor2), ['name' => 'Admin Renamed'])
            ->assertRedirect(route('conventions.show', $this->convention));
    });

    it('can delete any floor', function () {
        $floorId = $this->floor2->id;

        $this->actingAs($this->administrator)
            ->delete(route('floors.destroy', $this->floor2))
            ->assertRedirect(route('conventions.show', $this->convention));

        expect(Floor::find($floorId))->toBeNull();
    });

    it('can create sections on any floor', function () {
        $this->actingAs($this->administrator)
            ->post(route('sections.store', [$this->convention, $this->floor1]), [
                'name' => 'Admin Section',
                'number_of_seats' => 80,
            ])
            ->assertRedirect(route('floors.index', $this->convention));
    });

    it('can update any section', function () {
        $this->actingAs($this->administrator)
            ->put(route('sections.update', $this->section4), [
                'name' => 'Admin Updated Section',
                'number_of_seats' => 120,
            ])
            ->assertRedirect(route('floors.index', $this->convention));
    });

    it('can delete any section', function () {
        $sectionId = $this->section4->id;

        $this->actingAs($this->administrator)
            ->delete(route('sections.destroy', $this->section4))
            ->assertRedirect(route('floors.index', $this->convention));

        expect(Section::find($sectionId))->toBeNull();
    });

    it('can update occupancy on any section', function () {
        $this->actingAs($this->administrator)
            ->patch(route('sections.updateOccupancy', $this->section3), ['occupancy' => 50])
            ->assertRedirect();

        expect($this->section3->fresh()->occupancy)->toBe(50);
    });

    it('can start and stop attendance reports', function () {
        $this->actingAs($this->administrator)
            ->post(route('attendance.start', $this->convention))
            ->assertRedirect();

        $period = AttendancePeriod::where('convention_id', $this->convention->id)
            ->where('locked', false)->first();

        $this->actingAs($this->administrator)
            ->post(route('attendance.stop', [$this->convention, $period]))
            ->assertRedirect();

        expect($period->fresh()->locked)->toBeTrue();
    });

    it('can access search page', function () {
        $this->actingAs($this->administrator)
            ->get(route('search.index', $this->convention))
            ->assertOk();
    });

    it('can view users index and invite users', function () {
        $this->actingAs($this->administrator)
            ->get(route('users.index', $this->convention))
            ->assertOk();

        $this->actingAs($this->administrator)
            ->post(route('users.store', $this->convention), [
                'first_name' => 'Admin',
                'last_name' => 'Invited',
                'email' => 'admin-invited@example.com',
                'mobile' => '+1234567890',
                'roles' => ['Administrator'],
            ])
            ->assertRedirect();
    });

    it('can view floors index with all floors', function () {
        $response = $this->actingAs($this->administrator)
            ->get(route('floors.index', $this->convention));

        $response->assertOk();

        $floors = $response->original->getData()['page']['props']['floors'];
        expect(count($floors))->toBe(2);
    });
});

/*
|--------------------------------------------------------------------------
| 3. Outsider / No Access — Denied everywhere
|--------------------------------------------------------------------------
*/

describe('Outsider - no convention access', function () {
    it('CANNOT view convention show page', function () {
        $this->actingAs($this->outsider)
            ->get(route('conventions.show', $this->convention))
            ->assertForbidden();
    });

    it('CANNOT update convention', function () {
        $this->actingAs($this->outsider)
            ->put(route('conventions.update', $this->convention), [
                'name' => 'Hacked',
                'city' => 'X',
                'country' => 'X',
                'start_date' => now()->toDateString(),
                'end_date' => now()->addDay()->toDateString(),
            ])
            ->assertForbidden();
    });

    it('CANNOT delete convention', function () {
        $this->actingAs($this->outsider)
            ->delete(route('conventions.destroy', $this->convention))
            ->assertForbidden();
    });

    it('CANNOT export convention', function () {
        $this->actingAs($this->outsider)
            ->get(route('conventions.export', ['convention' => $this->convention, 'format' => 'md']))
            ->assertForbidden();
    });

    it('CANNOT access floors', function () {
        $this->actingAs($this->outsider)
            ->get(route('floors.index', $this->convention))
            ->assertForbidden();
    });

    it('CANNOT access users', function () {
        $this->actingAs($this->outsider)
            ->get(route('users.index', $this->convention))
            ->assertForbidden();
    });

    it('CANNOT access search', function () {
        $this->actingAs($this->outsider)
            ->get(route('search.index', $this->convention))
            ->assertForbidden();
    });

    it('CANNOT start attendance', function () {
        $this->actingAs($this->outsider)
            ->post(route('attendance.start', $this->convention))
            ->assertForbidden();
    });
});

/*
|--------------------------------------------------------------------------
| 4. Unauthenticated — Redirected to login or 403
|--------------------------------------------------------------------------
*/

describe('Unauthenticated access', function () {
    it('redirects to login for auth-required routes', function () {
        $this->get(route('conventions.index'))->assertRedirect(route('login'));
    });

    it('returns 403 for convention-access routes without session', function () {
        // These routes use EnsureConventionOrUrlAccess without auth middleware
        $this->get(route('conventions.show', $this->convention))->assertForbidden();
        $this->get(route('floors.index', $this->convention))->assertForbidden();
        $this->get(route('search.index', $this->convention))->assertForbidden();
    });

    it('redirects to login for auth-only convention routes', function () {
        $this->get(route('users.index', $this->convention))->assertRedirect(route('login'));
    });
});

/*
|--------------------------------------------------------------------------
| 5. Cross-role attendance report access
|--------------------------------------------------------------------------
*/

describe('Attendance report access by role', function () {
    it('allows Owner and Administrator to start/stop attendance', function () {
        // Owner starts
        $this->actingAs($this->owner)
            ->post(route('attendance.start', $this->convention))
            ->assertRedirect();

        $period = AttendancePeriod::where('convention_id', $this->convention->id)
            ->where('locked', false)->first();

        expect($period)->not->toBeNull();

        // Administrator can stop
        $this->actingAs($this->administrator)
            ->post(route('attendance.stop', [$this->convention, $period]))
            ->assertRedirect();

        expect($period->fresh()->locked)->toBeTrue();
    });
});
