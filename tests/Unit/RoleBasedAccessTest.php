<?php

use App\Models\Convention;
use App\Models\User;
use App\Policies\ConventionPolicy;
use Illuminate\Support\Facades\Gate;
use Tests\Helpers\ConventionTestHelper;

// --- Owner permissions ---

it('grants Owner full convention access', function () {
    $structure = ConventionTestHelper::createConventionWithStructure();
    $convention = $structure['convention'];
    $owner = $structure['owner'];

    $policy = new ConventionPolicy;

    expect($policy->view($owner, $convention))->toBeTrue()
        ->and($policy->update($owner, $convention))->toBeTrue()
        ->and($policy->delete($owner, $convention))->toBeTrue()
        ->and($policy->export($owner, $convention))->toBeTrue();
});

it('allows Owner to delete convention via HTTP', function () {
    $structure = ConventionTestHelper::createConventionWithStructure();
    $convention = $structure['convention'];
    $owner = $structure['owner'];

    // Bypass authorization since base Controller lacks AuthorizesRequests trait
    Gate::before(fn () => true);

    $this->actingAs($owner)
        ->delete(route('conventions.destroy', $convention))
        ->assertRedirect(route('conventions.index'));

    expect(Convention::find($convention->id))->toBeNull();
});

// --- Administrator permissions ---

it('grants Administrator read/write but not delete/export', function () {
    $structure = ConventionTestHelper::createConventionWithStructure();
    $convention = $structure['convention'];
    $admin = ConventionTestHelper::createUserWithRole($convention, 'Administrator');

    $policy = new ConventionPolicy;

    expect($policy->view($admin, $convention))->toBeTrue()
        ->and($policy->update($admin, $convention))->toBeTrue()
        ->and($policy->delete($admin, $convention))->toBeFalse()
        ->and($policy->export($admin, $convention))->toBeFalse();
});

it('allows Administrator to view convention show page', function () {
    $structure = ConventionTestHelper::createConventionWithStructure();
    $convention = $structure['convention'];
    $admin = ConventionTestHelper::createUserWithRole($convention, 'Administrator');

    $this->actingAs($admin)
        ->get(route('conventions.show', $convention))
        ->assertOk();
});

it('denies access to users with no role for the convention', function () {
    $structure = ConventionTestHelper::createConventionWithStructure();
    $convention = $structure['convention'];
    $outsider = User::factory()->create();

    $this->actingAs($outsider)
        ->get(route('conventions.show', $convention))
        ->assertForbidden();
});
