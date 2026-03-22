<?php

use App\Models\Convention;
use Illuminate\Support\Facades\DB;

/**
 * Feature: role-url-refactoring
 *
 * Property 3: Token generation and validity on convention creation
 *
 * For any newly created convention, section_url_token
 * must be a non-null string of at least 32 characters.
 *
 * **Validates: Requirements 2.1, 2.2, 2.3, 2.4**
 */
it('generates valid section_url_token on convention creation', function () {
    for ($i = 0; $i < 20; $i++) {
        $convention = Convention::factory()->create();

        $raw = DB::table('conventions')->where('id', $convention->id)->first();

        expect($raw->section_url_token)
            ->not->toBeNull("Iteration {$i}: section_url_token must not be null")
            ->toBeString()
            ->and(strlen($raw->section_url_token))
            ->toBeGreaterThanOrEqual(32, "Iteration {$i}: section_url_token must be at least 32 chars");
    }
})->group('property', 'role-url-refactoring');

it('generates tokens via model boot even when not provided in attributes', function () {
    for ($i = 0; $i < 10; $i++) {
        $convention = Convention::create([
            'name' => fake()->company().' Convention',
            'city' => fake()->city(),
            'country' => fake()->country(),
            'start_date' => now()->addDays(fake()->numberBetween(1, 30)),
            'end_date' => now()->addDays(fake()->numberBetween(31, 60)),
        ]);

        $raw = DB::table('conventions')->where('id', $convention->id)->first();

        expect($raw->section_url_token)
            ->not->toBeNull("Iteration {$i}: boot-generated section_url_token must not be null")
            ->and(strlen($raw->section_url_token))
            ->toBeGreaterThanOrEqual(32);
    }
})->group('property', 'role-url-refactoring');

/**
 * Property 4: Token uniqueness across conventions
 *
 * For any two distinct conventions, their section_url_token values must differ.
 *
 * **Validates: Requirements 2.6**
 */
it('generates unique section_url_token across conventions', function () {
    $count = 20;
    $conventions = Convention::factory()->count($count)->create();

    $sectionTokens = DB::table('conventions')
        ->whereIn('id', $conventions->pluck('id'))
        ->pluck('section_url_token')
        ->toArray();

    // All section tokens must be unique
    expect(count(array_unique($sectionTokens)))
        ->toBe($count, 'All section_url_tokens must be unique across conventions');
})->group('property', 'role-url-refactoring');
