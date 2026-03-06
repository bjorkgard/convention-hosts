<?php

use App\Models\AttendancePeriod;
use App\Models\AttendanceReport;
use App\Models\Convention;
use App\Models\Floor;
use App\Models\Section;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Tests\Helpers\ConventionTestHelper;

/*
|--------------------------------------------------------------------------
| Performance Tests
|--------------------------------------------------------------------------
| Verifies eager loading, pagination, and export performance.
*/

describe('Convention show page eager loading', function () {
    it('loads convention show page with minimal query count (no N+1)', function () {
        $structure = ConventionTestHelper::createConventionWithStructure([
            'floors' => 3,
            'sections_per_floor' => 4,
        ]);
        $convention = $structure['convention'];
        $owner = $structure['owner'];

        // Add extra users with roles
        for ($i = 0; $i < 5; $i++) {
            ConventionTestHelper::createUserWithRole($convention, 'ConventionUser');
        }

        // Add attendance periods with reports
        $period = AttendancePeriod::create([
            'convention_id' => $convention->id,
            'date' => now()->toDateString(),
            'period' => 'morning',
            'locked' => true,
        ]);

        foreach ($structure['sections']->take(4) as $section) {
            AttendanceReport::create([
                'attendance_period_id' => $period->id,
                'section_id' => $section->id,
                'attendance' => rand(10, 100),
                'reported_by' => $owner->id,
                'reported_at' => now(),
            ]);
        }

        // Count queries during page load
        DB::enableQueryLog();

        $response = $this->actingAs($owner)
            ->get(route('conventions.show', $convention));

        $queryLog = DB::getQueryLog();
        DB::disableQueryLog();

        $response->assertOk();

        // With eager loading, query count should stay bounded
        // regardless of number of floors/sections/users.
        // Without eager loading, this would be 30+ queries.
        expect(count($queryLog))->toBeLessThan(25);
    });

    it('loads convention index with eager-loaded relationships', function () {
        $owner = User::factory()->create();

        // Create multiple conventions
        for ($i = 0; $i < 5; $i++) {
            $convention = Convention::factory()->create();
            ConventionTestHelper::attachUserToConvention($owner, $convention, ['Owner', 'ConventionUser']);
            Floor::factory()->count(2)->create(['convention_id' => $convention->id]);
        }

        DB::enableQueryLog();

        $response = $this->actingAs($owner)
            ->get(route('conventions.index'));

        $queryLog = DB::getQueryLog();
        DB::disableQueryLog();

        $response->assertOk();

        // Should not scale linearly with convention count
        expect(count($queryLog))->toBeLessThan(15);
    });
});

describe('User index page query optimization', function () {
    it('loads users page without N+1 queries for roles and assignments', function () {
        $structure = ConventionTestHelper::createConventionWithStructure([
            'floors' => 2,
            'sections_per_floor' => 3,
        ]);
        $convention = $structure['convention'];
        $owner = $structure['owner'];

        // Add multiple users with different roles
        for ($i = 0; $i < 5; $i++) {
            ConventionTestHelper::createUserWithRole($convention, 'ConventionUser');
        }
        foreach ($structure['floors'] as $floor) {
            ConventionTestHelper::createUserWithRole($convention, 'FloorUser', [
                'floor_ids' => [$floor->id],
            ]);
        }

        DB::enableQueryLog();

        $response = $this->actingAs($owner)
            ->get(route('users.index', $convention));

        $queryLog = DB::getQueryLog();
        DB::disableQueryLog();

        $response->assertOk();

        // Batch loading roles/floors/sections should keep queries bounded
        expect(count($queryLog))->toBeLessThan(20);
    });
});

describe('Search results pagination', function () {
    it('returns paginated search results', function () {
        $structure = ConventionTestHelper::createConventionWithStructure([
            'floors' => 1,
            'sections_per_floor' => 0,
        ]);
        $convention = $structure['convention'];
        $owner = $structure['owner'];
        $floor = $structure['floors']->first();

        // Create 25 sections with low occupancy
        for ($i = 0; $i < 25; $i++) {
            Section::factory()->create([
                'floor_id' => $floor->id,
                'occupancy' => rand(0, 80),
                'number_of_seats' => 100,
            ]);
        }

        $response = $this->actingAs($owner)
            ->get(route('search.index', $convention));

        $response->assertOk();

        $sections = $response->original->getData()['page']['props']['sections'];

        // Should be paginated (max 20 per page)
        expect($sections['per_page'])->toBe(20);
        expect(count($sections['data']))->toBe(20);
        expect($sections['total'])->toBe(25);
    });
});

describe('Export performance', function () {
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

    it('exports convention data with reasonable data volume without timeout', function () {
        $structure = ConventionTestHelper::createConventionWithStructure([
            'floors' => 3,
            'sections_per_floor' => 5,
        ]);
        $convention = $structure['convention'];
        $owner = $structure['owner'];

        // Add attendance data
        $period = AttendancePeriod::create([
            'convention_id' => $convention->id,
            'date' => now()->toDateString(),
            'period' => 'morning',
            'locked' => true,
        ]);

        foreach ($structure['sections'] as $section) {
            AttendanceReport::create([
                'attendance_period_id' => $period->id,
                'section_id' => $section->id,
                'attendance' => rand(10, 200),
                'reported_by' => $owner->id,
                'reported_at' => now(),
            ]);
        }

        $startTime = microtime(true);

        $response = $this->actingAs($owner)
            ->get(route('conventions.export', ['convention' => $convention, 'format' => 'md']));

        $elapsed = microtime(true) - $startTime;

        expect($response->getStatusCode())->toBe(200);
        // Export should complete within 5 seconds for reasonable data
        expect($elapsed)->toBeLessThan(5.0);
    });
});

describe('Model query scopes', function () {
    it('Section::available() filters sections with occupancy < 90%', function () {
        $floor = Floor::factory()->create([
            'convention_id' => Convention::factory()->create()->id,
        ]);

        Section::factory()->create(['floor_id' => $floor->id, 'occupancy' => 50, 'number_of_seats' => 100]);
        Section::factory()->create(['floor_id' => $floor->id, 'occupancy' => 89, 'number_of_seats' => 100]);
        Section::factory()->create(['floor_id' => $floor->id, 'occupancy' => 90, 'number_of_seats' => 100]);
        Section::factory()->create(['floor_id' => $floor->id, 'occupancy' => 100, 'number_of_seats' => 100]);

        $available = Section::available()->where('floor_id', $floor->id)->get();

        expect($available)->toHaveCount(2);
        expect($available->pluck('occupancy')->sort()->values()->toArray())->toBe([50, 89]);
    });

    it('AttendancePeriod::active() filters unlocked periods', function () {
        $convention = Convention::factory()->create();

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
            'locked' => false,
        ]);

        $active = AttendancePeriod::active()
            ->where('convention_id', $convention->id)
            ->get();

        expect($active)->toHaveCount(1);
        expect($active->first()->period)->toBe('afternoon');
    });

    it('AttendancePeriod::forToday() filters periods for current date', function () {
        $convention = Convention::factory()->create();

        AttendancePeriod::create([
            'convention_id' => $convention->id,
            'date' => now()->toDateString(),
            'period' => 'morning',
            'locked' => false,
        ]);
        AttendancePeriod::create([
            'convention_id' => $convention->id,
            'date' => now()->subDay()->toDateString(),
            'period' => 'morning',
            'locked' => true,
        ]);

        $today = AttendancePeriod::forToday()
            ->where('convention_id', $convention->id)
            ->get();

        expect($today)->toHaveCount(1);
        expect($today->first()->date->toDateString())->toBe(now()->toDateString());
    });
});
