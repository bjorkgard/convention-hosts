<?php

use App\Models\Convention;
use App\Models\User;
use Tests\Helpers\ConventionTestHelper;

it('shares flash error messages as inertia props', function () {
    $structure = ConventionTestHelper::createConventionWithStructure();
    $owner = $structure['owner'];
    $convention = $structure['convention'];

    // Create 2 locked periods today to hit the max limit
    \App\Models\AttendancePeriod::create([
        'convention_id' => $convention->id,
        'date' => now()->toDateString(),
        'period' => 'morning',
        'locked' => true,
    ]);
    \App\Models\AttendancePeriod::create([
        'convention_id' => $convention->id,
        'date' => now()->toDateString(),
        'period' => 'afternoon',
        'locked' => true,
    ]);

    // This will fail with an error flash message
    $this->actingAs($owner)
        ->post(route('attendance.start', $convention))
        ->assertRedirect();

    // Flash an error directly to verify middleware shares it
    $this->actingAs($owner)
        ->withSession(['error' => 'Maximum of 2 attendance reports per day has been reached.'])
        ->get(route('conventions.show', $convention))
        ->assertInertia(fn ($page) => $page->whereNotNull('flash.error'));
});
