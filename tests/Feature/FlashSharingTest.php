<?php

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

    // This triggers the service exception and should flash an error
    $this->actingAs($owner)
        ->post(route('attendance.start', $convention))
        ->assertRedirect();

    // The next page load should have the flash error in Inertia props
    $this->actingAs($owner)
        ->get(route('conventions.show', $convention))
        ->assertInertia(fn ($page) => $page->whereNotNull('flash.error'));
});
