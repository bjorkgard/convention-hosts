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
| Requirements: 5.4, 5.5, 5.6, 5.7, 12.1, 12.2, 12.3
|
*/

beforeEach(function () {
    Mail::fake();

    // Create a convention with 2 floors, 2 sections per floor
    $this->structure = ConventionTestHelper::createConventionWithStructure([
        'floors' => 2,
        'sections_per_floor' => 2,
    ]);

    $this->convention = $this->structure['convention'];
    $this->owner = $this->structure['owner'];
    $this->floor1 = $this->structure['floors'][0];
    $this->floor2 = $this->structure['floors'][1];
    $this->section1 = $this->structure['sections'][0]; // floor1
    $this->section2 = $this->structure['sections'][1]; // floor1
    $this->section3 = $this->structure['sections'][2]; // floor2
    $this->section4 = $this->structure['sections'][3]; // floor2

    // Create users for each role
    $this->conventionUser = ConventionTestHelper::createUserWithRole(
        $this->convention, 'ConventionUser'
    );

    $this->floorUser = ConventionTestHelper::createUserWithRole(
        $this->convention, 'FloorUser', [
            'floor_ids' => [$this->floor1->id],
        ]
    );

    $this->sectionUser = ConventionTestHelper::createUserWithRole(
        $this->convention, 'SectionUser', [
            'section_ids' => [$this->section1->id],
        ]
    );

    $this->outsider = User::factory()->create();

    // Ensure export directory exists
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
| 1. Owner Access — Full Control (Req 5.4, 12.1, 12.4, 12.5)
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
            ->assertRedirect(route('conventions.show', $this->convention));
    });

    it('can update any section', function () {
        $this->actingAs($this->owner)
            ->put(route('sections.update', $this->section3), [
                'name' => 'Renamed Section',
                'number_of_seats' => 150,
            ])
            ->assertRedirect(route('sections.show', $this->section3));
    });

    it('can delete any section', function () {
        $sectionId = $this->section4->id;

        $this->actingAs($this->owner)
            ->delete(route('sections.destroy', $this->section4))
            ->assertRedirect(route('conventions.show', $this->convention));

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
                'roles' => ['ConventionUser'],
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
| 2. ConventionUser Access — Convention-wide, no delete/export (Req 5.5, 12.1)
|--------------------------------------------------------------------------
*/

describe('ConventionUser access - convention-wide read/write', function () {
    it('can view convention show page', function () {
        $this->actingAs($this->conventionUser)
            ->get(route('conventions.show', $this->convention))
            ->assertOk();
    });

    it('can update convention details', function () {
        $this->actingAs($this->conventionUser)
            ->put(route('conventions.update', $this->convention), [
                'name' => 'CU Updated',
                'city' => $this->convention->city,
                'country' => $this->convention->country,
                'start_date' => $this->convention->start_date->toDateString(),
                'end_date' => $this->convention->end_date->toDateString(),
            ])
            ->assertRedirect(route('conventions.show', $this->convention));
    });

    it('CANNOT delete convention', function () {
        $this->actingAs($this->conventionUser)
            ->delete(route('conventions.destroy', $this->convention))
            ->assertForbidden();
    });

    it('CANNOT export convention data', function () {
        $this->actingAs($this->conventionUser)
            ->get(route('conventions.export', ['convention' => $this->convention, 'format' => 'md']))
            ->assertForbidden();
    });

    it('can create floors', function () {
        $this->actingAs($this->conventionUser)
            ->post(route('floors.store', $this->convention), ['name' => 'CU Floor'])
            ->assertRedirect(route('conventions.show', $this->convention));
    });

    it('can update any floor', function () {
        $this->actingAs($this->conventionUser)
            ->put(route('floors.update', $this->floor2), ['name' => 'CU Renamed'])
            ->assertRedirect(route('conventions.show', $this->convention));
    });

    it('can delete any floor', function () {
        $floorId = $this->floor2->id;

        $this->actingAs($this->conventionUser)
            ->delete(route('floors.destroy', $this->floor2))
            ->assertRedirect(route('conventions.show', $this->convention));

        expect(Floor::find($floorId))->toBeNull();
    });

    it('can create sections on any floor', function () {
        $this->actingAs($this->conventionUser)
            ->post(route('sections.store', [$this->convention, $this->floor1]), [
                'name' => 'CU Section',
                'number_of_seats' => 80,
            ])
            ->assertRedirect(route('conventions.show', $this->convention));
    });

    it('can update any section', function () {
        $this->actingAs($this->conventionUser)
            ->put(route('sections.update', $this->section4), [
                'name' => 'CU Updated Section',
                'number_of_seats' => 120,
            ])
            ->assertRedirect(route('sections.show', $this->section4));
    });

    it('can delete any section', function () {
        $sectionId = $this->section4->id;

        $this->actingAs($this->conventionUser)
            ->delete(route('sections.destroy', $this->section4))
            ->assertRedirect(route('conventions.show', $this->convention));

        expect(Section::find($sectionId))->toBeNull();
    });

    it('can update occupancy on any section', function () {
        $this->actingAs($this->conventionUser)
            ->patch(route('sections.updateOccupancy', $this->section3), ['occupancy' => 50])
            ->assertRedirect();

        expect($this->section3->fresh()->occupancy)->toBe(50);
    });

    it('can start and stop attendance reports', function () {
        $this->actingAs($this->conventionUser)
            ->post(route('attendance.start', $this->convention))
            ->assertRedirect();

        $period = AttendancePeriod::where('convention_id', $this->convention->id)
            ->where('locked', false)->first();

        $this->actingAs($this->conventionUser)
            ->post(route('attendance.stop', [$this->convention, $period]))
            ->assertRedirect();

        expect($period->fresh()->locked)->toBeTrue();
    });

    it('can access search page', function () {
        $this->actingAs($this->conventionUser)
            ->get(route('search.index', $this->convention))
            ->assertOk();
    });

    it('can view users index and invite users', function () {
        $this->actingAs($this->conventionUser)
            ->get(route('users.index', $this->convention))
            ->assertOk();

        $this->actingAs($this->conventionUser)
            ->post(route('users.store', $this->convention), [
                'first_name' => 'CU',
                'last_name' => 'Invited',
                'email' => 'cu-invited@example.com',
                'mobile' => '+1234567890',
                'roles' => ['SectionUser'],
                'section_ids' => [$this->section1->id],
            ])
            ->assertRedirect();
    });

    it('can view floors index with all floors', function () {
        $response = $this->actingAs($this->conventionUser)
            ->get(route('floors.index', $this->convention));

        $response->assertOk();

        $floors = $response->original->getData()['page']['props']['floors'];
        expect(count($floors))->toBe(2);
    });
});

/*
|--------------------------------------------------------------------------
| 3. FloorUser Scoping — Assigned floors only (Req 5.6, 12.2, 13.1, 13.2, 13.3)
|--------------------------------------------------------------------------
*/

describe('FloorUser scoping - assigned floors only', function () {
    it('can view convention show page', function () {
        $this->actingAs($this->floorUser)
            ->get(route('conventions.show', $this->convention))
            ->assertOk();
    });

    it('sees only assigned floors on floors index', function () {
        $response = $this->actingAs($this->floorUser)
            ->get(route('floors.index', $this->convention));

        $response->assertOk();

        $floors = $response->original->getData()['page']['props']['floors'];
        $floorIds = collect($floors)->pluck('id')->toArray();

        expect($floorIds)->toContain($this->floor1->id)
            ->not->toContain($this->floor2->id);
    });

    it('CANNOT create floors', function () {
        $this->actingAs($this->floorUser)
            ->post(route('floors.store', $this->convention), ['name' => 'FU Floor'])
            ->assertForbidden();
    });

    it('can update assigned floor', function () {
        $this->actingAs($this->floorUser)
            ->put(route('floors.update', $this->floor1), ['name' => 'FU Renamed'])
            ->assertRedirect(route('conventions.show', $this->convention));

        expect($this->floor1->fresh()->name)->toBe('FU Renamed');
    });

    it('CANNOT update unassigned floor', function () {
        $this->actingAs($this->floorUser)
            ->put(route('floors.update', $this->floor2), ['name' => 'Should Fail'])
            ->assertForbidden();
    });

    it('CANNOT delete any floor', function () {
        $this->actingAs($this->floorUser)
            ->delete(route('floors.destroy', $this->floor1))
            ->assertForbidden();

        $this->actingAs($this->floorUser)
            ->delete(route('floors.destroy', $this->floor2))
            ->assertForbidden();
    });

    it('can create sections on assigned floor', function () {
        $this->actingAs($this->floorUser)
            ->post(route('sections.store', [$this->convention, $this->floor1]), [
                'name' => 'FU Section',
                'number_of_seats' => 60,
            ])
            ->assertRedirect(route('conventions.show', $this->convention));
    });

    it('CANNOT create sections on unassigned floor', function () {
        $this->actingAs($this->floorUser)
            ->post(route('sections.store', [$this->convention, $this->floor2]), [
                'name' => 'Should Fail',
                'number_of_seats' => 60,
            ])
            ->assertForbidden();
    });

    it('can update sections on assigned floor', function () {
        $this->actingAs($this->floorUser)
            ->put(route('sections.update', $this->section1), [
                'name' => 'FU Updated',
                'number_of_seats' => 90,
            ])
            ->assertRedirect(route('sections.show', $this->section1));
    });

    it('CANNOT update sections on unassigned floor', function () {
        $this->actingAs($this->floorUser)
            ->put(route('sections.update', $this->section3), [
                'name' => 'Should Fail',
                'number_of_seats' => 90,
            ])
            ->assertForbidden();
    });

    it('can delete sections on assigned floor', function () {
        $sectionId = $this->section2->id;

        $this->actingAs($this->floorUser)
            ->delete(route('sections.destroy', $this->section2))
            ->assertRedirect(route('conventions.show', $this->convention));

        expect(Section::find($sectionId))->toBeNull();
    });

    it('CANNOT delete sections on unassigned floor', function () {
        $this->actingAs($this->floorUser)
            ->delete(route('sections.destroy', $this->section3))
            ->assertForbidden();
    });

    it('can update occupancy on sections of assigned floor', function () {
        $this->actingAs($this->floorUser)
            ->patch(route('sections.updateOccupancy', $this->section1), ['occupancy' => 25])
            ->assertRedirect();

        expect($this->section1->fresh()->occupancy)->toBe(25);
    });

    it('CANNOT update occupancy on sections of unassigned floor', function () {
        $this->actingAs($this->floorUser)
            ->patch(route('sections.updateOccupancy', $this->section3), ['occupancy' => 25])
            ->assertForbidden();
    });

    it('CANNOT delete convention', function () {
        $this->actingAs($this->floorUser)
            ->delete(route('conventions.destroy', $this->convention))
            ->assertForbidden();
    });

    it('CANNOT export convention data', function () {
        $this->actingAs($this->floorUser)
            ->get(route('conventions.export', ['convention' => $this->convention, 'format' => 'md']))
            ->assertForbidden();
    });

    it('CANNOT start attendance reports', function () {
        $this->actingAs($this->floorUser)
            ->post(route('attendance.start', $this->convention))
            ->assertForbidden();
    });

    it('can access search page', function () {
        $this->actingAs($this->floorUser)
            ->get(route('search.index', $this->convention))
            ->assertOk();
    });
});

/*
|--------------------------------------------------------------------------
| 4. SectionUser Scoping — Assigned sections only (Req 5.7, 12.3, 14.1, 14.2)
|--------------------------------------------------------------------------
*/

describe('SectionUser scoping - assigned sections only', function () {
    it('can view convention show page', function () {
        $this->actingAs($this->sectionUser)
            ->get(route('conventions.show', $this->convention))
            ->assertOk();
    });

    it('CANNOT create floors', function () {
        $this->actingAs($this->sectionUser)
            ->post(route('floors.store', $this->convention), ['name' => 'SU Floor'])
            ->assertForbidden();
    });

    it('CANNOT update any floor', function () {
        $this->actingAs($this->sectionUser)
            ->put(route('floors.update', $this->floor1), ['name' => 'Should Fail'])
            ->assertForbidden();
    });

    it('CANNOT delete any floor', function () {
        $this->actingAs($this->sectionUser)
            ->delete(route('floors.destroy', $this->floor1))
            ->assertForbidden();
    });

    it('CANNOT create sections', function () {
        $this->actingAs($this->sectionUser)
            ->post(route('sections.store', [$this->convention, $this->floor1]), [
                'name' => 'Should Fail',
                'number_of_seats' => 50,
            ])
            ->assertForbidden();
    });

    it('can update assigned section', function () {
        $this->actingAs($this->sectionUser)
            ->put(route('sections.update', $this->section1), [
                'name' => 'SU Updated',
                'number_of_seats' => $this->section1->number_of_seats,
            ])
            ->assertRedirect(route('sections.show', $this->section1));
    });

    it('CANNOT update unassigned section', function () {
        $this->actingAs($this->sectionUser)
            ->put(route('sections.update', $this->section2), [
                'name' => 'Should Fail',
                'number_of_seats' => $this->section2->number_of_seats,
            ])
            ->assertForbidden();
    });

    it('CANNOT delete any section', function () {
        $this->actingAs($this->sectionUser)
            ->delete(route('sections.destroy', $this->section1))
            ->assertForbidden();

        $this->actingAs($this->sectionUser)
            ->delete(route('sections.destroy', $this->section3))
            ->assertForbidden();
    });

    it('can update occupancy on assigned section', function () {
        $this->actingAs($this->sectionUser)
            ->patch(route('sections.updateOccupancy', $this->section1), ['occupancy' => 50])
            ->assertRedirect();

        expect($this->section1->fresh()->occupancy)->toBe(50);
    });

    it('can set assigned section to full', function () {
        $this->actingAs($this->sectionUser)
            ->post(route('sections.setFull', $this->section1))
            ->assertRedirect();

        expect($this->section1->fresh()->occupancy)->toBe(100);
    });

    it('CANNOT update occupancy on unassigned section', function () {
        $this->actingAs($this->sectionUser)
            ->patch(route('sections.updateOccupancy', $this->section2), ['occupancy' => 50])
            ->assertForbidden();
    });

    it('CANNOT set unassigned section to full', function () {
        $this->actingAs($this->sectionUser)
            ->post(route('sections.setFull', $this->section2))
            ->assertForbidden();
    });

    it('CANNOT delete convention', function () {
        $this->actingAs($this->sectionUser)
            ->delete(route('conventions.destroy', $this->convention))
            ->assertForbidden();
    });

    it('CANNOT export convention data', function () {
        $this->actingAs($this->sectionUser)
            ->get(route('conventions.export', ['convention' => $this->convention, 'format' => 'md']))
            ->assertForbidden();
    });

    it('CANNOT start attendance reports', function () {
        $this->actingAs($this->sectionUser)
            ->post(route('attendance.start', $this->convention))
            ->assertForbidden();
    });

    it('can access search page', function () {
        $this->actingAs($this->sectionUser)
            ->get(route('search.index', $this->convention))
            ->assertOk();
    });
});

/*
|--------------------------------------------------------------------------
| 5. Outsider / No Access — Denied everywhere (Req 5.2)
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
| 6. Unauthenticated — Redirected to login
|--------------------------------------------------------------------------
*/

describe('Unauthenticated access', function () {
    it('redirects to login for all convention routes', function () {
        $this->get(route('conventions.index'))->assertRedirect(route('login'));
        $this->get(route('conventions.show', $this->convention))->assertRedirect(route('login'));
        $this->get(route('floors.index', $this->convention))->assertRedirect(route('login'));
        $this->get(route('users.index', $this->convention))->assertRedirect(route('login'));
        $this->get(route('search.index', $this->convention))->assertRedirect(route('login'));
    });
});

/*
|--------------------------------------------------------------------------
| 7. Cross-role attendance report access
|--------------------------------------------------------------------------
*/

describe('Attendance report access by role', function () {
    it('allows Owner and ConventionUser to start/stop, denies FloorUser and SectionUser', function () {
        // Owner starts
        $this->actingAs($this->owner)
            ->post(route('attendance.start', $this->convention))
            ->assertRedirect();

        $period = AttendancePeriod::where('convention_id', $this->convention->id)
            ->where('locked', false)->first();

        expect($period)->not->toBeNull();

        // FloorUser cannot stop
        $this->actingAs($this->floorUser)
            ->post(route('attendance.stop', [$this->convention, $period]))
            ->assertForbidden();

        // SectionUser cannot stop
        $this->actingAs($this->sectionUser)
            ->post(route('attendance.stop', [$this->convention, $period]))
            ->assertForbidden();

        // ConventionUser can stop
        $this->actingAs($this->conventionUser)
            ->post(route('attendance.stop', [$this->convention, $period]))
            ->assertRedirect();

        expect($period->fresh()->locked)->toBeTrue();
    });

    it('allows any convention member to report attendance for accessible sections', function () {
        $this->actingAs($this->owner)
            ->post(route('attendance.start', $this->convention));

        $period = AttendancePeriod::where('convention_id', $this->convention->id)
            ->where('locked', false)->first();

        // SectionUser can report on assigned section
        $this->actingAs($this->sectionUser)
            ->post(route('attendance.report', [$this->section1, $period]), [
                'attendance' => 42,
                'period_id' => $period->id,
            ])
            ->assertRedirect();
    });
});
