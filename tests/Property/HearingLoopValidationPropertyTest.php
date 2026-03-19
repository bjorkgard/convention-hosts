<?php

use Tests\Helpers\ConventionTestHelper;

// Feature: hearing-loop-section, Property 2: Validation accepts valid booleans and rejects invalid values
// Validates: Requirements 3.1, 3.2, 5.4

it('accepts valid hearing_loop values on StoreSectionRequest, UpdateSectionRequest, and SearchRequest', function () {
    $structure = ConventionTestHelper::createConventionWithStructure([
        'floors' => 1,
        'sections_per_floor' => 1,
    ]);
    $owner = $structure['owner'];
    $convention = $structure['convention'];
    $floor = $structure['floors']->first();
    $section = $structure['sections']->first();

    $validValues = [true, false, 1, 0, null, '1', '0'];

    for ($i = 0; $i < 100; $i++) {
        $value = fake()->randomElement($validValues);

        // StoreSectionRequest (Req 3.1)
        $storePayload = [
            'name' => 'Store Section '.$i,
            'number_of_seats' => fake()->numberBetween(1, 500),
        ];
        if ($value !== null) {
            $storePayload['hearing_loop'] = $value;
        }

        $response = $this->actingAs($owner)
            ->post(route('sections.store', [$convention, $floor]), $storePayload);

        expect($response->isRedirect())->toBeTrue("Iteration {$i} (store): valid value ".var_export($value, true).' should be accepted');
        $response->assertSessionDoesntHaveErrors(['hearing_loop']);

        // UpdateSectionRequest (Req 3.2)
        $updatePayload = [
            'name' => 'Updated Section '.$i,
            'number_of_seats' => fake()->numberBetween(1, 500),
        ];
        if ($value !== null) {
            $updatePayload['hearing_loop'] = $value;
        }

        $response = $this->actingAs($owner)
            ->put(route('sections.update', $section), $updatePayload);

        expect($response->isRedirect())->toBeTrue("Iteration {$i} (update): valid value ".var_export($value, true).' should be accepted');
        $response->assertSessionDoesntHaveErrors(['hearing_loop']);

        // SearchRequest (Req 5.4)
        $searchParams = ['convention' => $convention->id];
        if ($value !== null) {
            $searchParams['hearing_loop'] = $value;
        }

        $response = $this->actingAs($owner)
            ->get(route('search.index', $searchParams));

        $response->assertOk("Iteration {$i} (search): valid value ".var_export($value, true).' should be accepted');
    }
})->group('property', 'hearing-loop');

it('rejects invalid hearing_loop values on StoreSectionRequest, UpdateSectionRequest, and SearchRequest', function () {
    $structure = ConventionTestHelper::createConventionWithStructure([
        'floors' => 1,
        'sections_per_floor' => 1,
    ]);
    $owner = $structure['owner'];
    $convention = $structure['convention'];
    $floor = $structure['floors']->first();
    $section = $structure['sections']->first();

    // Only scalar invalid values — arrays can't be reliably sent via GET query params
    $invalidScalarValues = ['yes', 'no', 'true', 'on', 'off', 2, -1, 0.5, 3.14, 'abc'];
    $invalidArrayValues = [[1], ['yes']];

    for ($i = 0; $i < 100; $i++) {
        $scalarValue = fake()->randomElement($invalidScalarValues);

        // StoreSectionRequest (Req 3.1)
        $storePayload = [
            'name' => 'Store Section '.$i,
            'number_of_seats' => fake()->numberBetween(1, 500),
            'hearing_loop' => $scalarValue,
        ];

        $response = $this->actingAs($owner)
            ->post(route('sections.store', [$convention, $floor]), $storePayload);

        $response->assertSessionHasErrors('hearing_loop');

        // UpdateSectionRequest (Req 3.2)
        $updatePayload = [
            'name' => 'Updated Section '.$i,
            'number_of_seats' => fake()->numberBetween(1, 500),
            'hearing_loop' => $scalarValue,
        ];

        $response = $this->actingAs($owner)
            ->put(route('sections.update', $section), $updatePayload);

        $response->assertSessionHasErrors('hearing_loop');

        // SearchRequest (Req 5.4) — scalar invalid values via GET
        $response = $this->actingAs($owner)
            ->get(route('search.index', [
                'convention' => $convention->id,
                'hearing_loop' => $scalarValue,
            ]));

        $response->assertSessionHasErrors('hearing_loop');
    }

    // Also test array values on POST requests (store + update only)
    for ($i = 0; $i < 10; $i++) {
        $arrayValue = fake()->randomElement($invalidArrayValues);

        $response = $this->actingAs($owner)
            ->post(route('sections.store', [$convention, $floor]), [
                'name' => 'Array Store '.$i,
                'number_of_seats' => fake()->numberBetween(1, 500),
                'hearing_loop' => $arrayValue,
            ]);

        $response->assertSessionHasErrors('hearing_loop');

        $response = $this->actingAs($owner)
            ->put(route('sections.update', $section), [
                'name' => 'Array Update '.$i,
                'number_of_seats' => fake()->numberBetween(1, 500),
                'hearing_loop' => $arrayValue,
            ]);

        $response->assertSessionHasErrors('hearing_loop');
    }
})->group('property', 'hearing-loop');
