<?php

use App\Concerns\SanitizesInput;
use App\Models\Convention;
use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\DB;

beforeEach(function () {
    $this->user = User::factory()->create([
        'first_name' => 'Test',
        'last_name' => 'User',
        'email' => 'test@example.com',
        'password' => bcrypt('Password1!'),
    ]);
});

function createConventionWithOwner(User $user): Convention
{
    $convention = Convention::factory()->create();
    $convention->users()->attach($user->id);
    DB::table('convention_user_roles')->insert([
        ['convention_id' => $convention->id, 'user_id' => $user->id, 'role' => 'Owner'],
        ['convention_id' => $convention->id, 'user_id' => $user->id, 'role' => 'ConventionUser'],
    ]);

    return $convention;
}

// ── Integration tests via HTTP routes that work ──

test('convention creation trims whitespace from string inputs', function () {
    $this->actingAs($this->user);

    $this->post(route('conventions.store'), [
        'name' => '  Tech Conference  ',
        'city' => '  New York  ',
        'country' => '  USA  ',
        'address' => '  123 Main St  ',
        'start_date' => now()->addMonth()->format('Y-m-d'),
        'end_date' => now()->addMonth()->addDays(3)->format('Y-m-d'),
        'other_info' => '  Some info  ',
    ]);

    $created = Convention::where('name', 'Tech Conference')->first();
    expect($created)->not->toBeNull()
        ->and($created->city)->toBe('New York')
        ->and($created->country)->toBe('USA')
        ->and($created->address)->toBe('123 Main St');
});

test('convention creation strips HTML tags from plain text fields', function () {
    $this->actingAs($this->user);

    $this->post(route('conventions.store'), [
        'name' => '<script>alert("xss")</script>Tech Conference',
        'city' => '<b>New York</b>',
        'country' => 'USA<img src=x onerror=alert(1)>',
        'start_date' => now()->addMonth()->format('Y-m-d'),
        'end_date' => now()->addMonth()->addDays(3)->format('Y-m-d'),
    ]);

    $created = Convention::where('name', 'alert("xss")Tech Conference')->first();
    expect($created)->not->toBeNull()
        ->and($created->city)->toBe('New York')
        ->and($created->country)->toBe('USA');
});

test('convention other_info preserves allowed HTML tags as rich text', function () {
    $this->actingAs($this->user);

    $this->post(route('conventions.store'), [
        'name' => 'Rich Text Convention',
        'city' => 'Berlin',
        'country' => 'Germany',
        'start_date' => now()->addMonth()->format('Y-m-d'),
        'end_date' => now()->addMonth()->addDays(3)->format('Y-m-d'),
        'other_info' => '<p>Important <strong>info</strong></p><script>alert("xss")</script>',
    ]);

    $created = Convention::where('name', 'Rich Text Convention')->first();
    expect($created)->not->toBeNull()
        ->and($created->other_info)->toBe('<p>Important <strong>info</strong></p>alert("xss")');
});

test('convention other_info strips event handler attributes', function () {
    $this->actingAs($this->user);

    $this->post(route('conventions.store'), [
        'name' => 'Event Handler Convention',
        'city' => 'Paris',
        'country' => 'France',
        'start_date' => now()->addMonth()->format('Y-m-d'),
        'end_date' => now()->addMonth()->addDays(3)->format('Y-m-d'),
        'other_info' => '<p onclick="alert(1)">Click me</p>',
    ]);

    $created = Convention::where('name', 'Event Handler Convention')->first();
    expect($created)->not->toBeNull()
        ->and($created->other_info)->not->toContain('onclick');
});

test('user creation trims and strips HTML from text fields', function () {
    $this->actingAs($this->user);
    $convention = createConventionWithOwner($this->user);

    $this->post(route('users.store', $convention), [
        'first_name' => '  <b>John</b>  ',
        'last_name' => '  <script>alert("x")</script>Doe  ',
        'email' => 'john.doe@example.com',
        'mobile' => '  +1234567890  ',
        'roles' => ['ConventionUser'],
    ]);

    $created = User::where('email', 'john.doe@example.com')->first();
    expect($created)->not->toBeNull()
        ->and($created->first_name)->toBe('John')
        ->and($created->last_name)->toBe('alert("x")Doe')
        ->and($created->mobile)->toBe('+1234567890');
});

// ── Unit tests for the SanitizesInput trait directly ──

test('sanitizeString trims whitespace and strips all HTML tags', function () {
    $request = new class extends FormRequest
    {
        use SanitizesInput;
    };

    $reflection = new ReflectionMethod($request, 'sanitizeString');

    expect($reflection->invoke($request, '  hello  '))->toBe('hello')
        ->and($reflection->invoke($request, '<b>bold</b>'))->toBe('bold')
        ->and($reflection->invoke($request, '<script>alert("xss")</script>text'))->toBe('alert("xss")text')
        ->and($reflection->invoke($request, '  <p>paragraph</p>  '))->toBe('paragraph');
});

test('sanitizeRichText preserves allowed tags and strips dangerous ones', function () {
    $request = new class extends FormRequest
    {
        use SanitizesInput;
    };

    $reflection = new ReflectionMethod($request, 'sanitizeRichText');

    // Preserves allowed tags
    expect($reflection->invoke($request, '<p>Hello <strong>world</strong></p>'))
        ->toBe('<p>Hello <strong>world</strong></p>');

    // Strips script tags
    expect($reflection->invoke($request, '<p>Safe</p><script>alert("xss")</script>'))
        ->toBe('<p>Safe</p>alert("xss")');

    // Strips div, span (not in allowed list)
    expect($reflection->invoke($request, '<div><span>text</span></div>'))
        ->toBe('text');

    // Preserves list tags
    expect($reflection->invoke($request, '<ul><li>Item 1</li><li>Item 2</li></ul>'))
        ->toBe('<ul><li>Item 1</li><li>Item 2</li></ul>');

    // Trims whitespace
    expect($reflection->invoke($request, '  <p>trimmed</p>  '))
        ->toBe('<p>trimmed</p>');
});

test('sanitizeRichText strips event handler attributes', function () {
    $request = new class extends FormRequest
    {
        use SanitizesInput;
    };

    $reflection = new ReflectionMethod($request, 'sanitizeRichText');

    expect($reflection->invoke($request, '<p onclick="alert(1)">Click</p>'))
        ->not->toContain('onclick');

    expect($reflection->invoke($request, '<a onmouseover="steal()">Link</a>'))
        ->not->toContain('onmouseover');
});

test('sanitizeRichText strips javascript protocol from href', function () {
    $request = new class extends FormRequest
    {
        use SanitizesInput;
    };

    $reflection = new ReflectionMethod($request, 'sanitizeRichText');

    $result = $reflection->invoke($request, '<a href="javascript:alert(1)">Click</a>');
    expect($result)->not->toContain('javascript:');
});

test('sanitization skips password fields', function () {
    $request = new class extends FormRequest
    {
        use SanitizesInput;
    };

    $excluded = (new ReflectionMethod($request, 'excludedFromSanitization'))->invoke($request);

    expect($excluded)->toContain('password')
        ->and($excluded)->toContain('password_confirmation')
        ->and($excluded)->toContain('current_password');
});

test('sanitization skips non-string values', function () {
    // Verify the trait only processes strings by checking the logic
    $request = new class extends FormRequest
    {
        use SanitizesInput;
    };

    // Integers, booleans, arrays, and nulls should not be affected
    // The trait checks `is_string($value)` before processing
    $reflection = new ReflectionMethod($request, 'sanitizeInputs');

    // Create a request with mixed types
    $request->merge([
        'name' => '  <b>Test</b>  ',
        'count' => 42,
        'active' => true,
        'tags' => ['a', 'b'],
        'empty' => null,
    ]);

    $reflection->invoke($request);

    expect($request->input('name'))->toBe('Test')
        ->and($request->input('count'))->toBe(42)
        ->and($request->input('active'))->toBe(true)
        ->and($request->input('tags'))->toBe(['a', 'b'])
        ->and($request->input('empty'))->toBeNull();
});

test('StoreConventionRequest has other_info as rich text field', function () {
    $request = new \App\Http\Requests\StoreConventionRequest;
    $richTextFields = (new ReflectionMethod($request, 'richTextFields'))->invoke($request);

    expect($richTextFields)->toContain('other_info');
});

test('StoreSectionRequest has information as rich text field', function () {
    $request = new \App\Http\Requests\StoreSectionRequest;
    $richTextFields = (new ReflectionMethod($request, 'richTextFields'))->invoke($request);

    expect($richTextFields)->toContain('information');
});

test('all form requests use SanitizesInput trait', function () {
    $formRequests = [
        \App\Http\Requests\StoreConventionRequest::class,
        \App\Http\Requests\UpdateConventionRequest::class,
        \App\Http\Requests\StoreFloorRequest::class,
        \App\Http\Requests\StoreSectionRequest::class,
        \App\Http\Requests\StoreUserRequest::class,
        \App\Http\Requests\UpdateUserRequest::class,
        \App\Http\Requests\UpdateOccupancyRequest::class,
        \App\Http\Requests\ReportAttendanceRequest::class,
        \App\Http\Requests\SearchRequest::class,
        \App\Http\Requests\SetPasswordRequest::class,
        \App\Http\Requests\Settings\ProfileUpdateRequest::class,
    ];

    foreach ($formRequests as $class) {
        $traits = class_uses_recursive($class);
        expect(in_array(SanitizesInput::class, $traits, true))
            ->toBeTrue("Missing SanitizesInput in {$class}");
    }
});
