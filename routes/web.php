<?php

use App\Http\Controllers\AttendanceController;
use App\Http\Controllers\Auth\InvitationController;
use App\Http\Controllers\ConventionController;
use App\Http\Controllers\FloorController;
use App\Http\Controllers\SearchController;
use App\Http\Controllers\SectionController;
use App\Http\Controllers\UserController;
use App\Http\Middleware\EnsureConventionAccess;
use App\Http\Middleware\EnsureOwnerRole;
use App\Http\Middleware\ScopeByRole;
use Illuminate\Support\Facades\Route;
use Laravel\Fortify\Features;

Route::inertia('/', 'welcome', [
    'canRegister' => Features::enabled(Features::registration()),
])->name('home');

// Invitation routes (outside auth - user is not yet authenticated)
Route::get('invitation/{user}/{convention}', [InvitationController::class, 'show'])
    ->name('invitation.show')
    ->middleware('signed');
Route::post('invitation/{user}/{convention}', [InvitationController::class, 'store'])
    ->name('invitation.store');

// Email confirmation route (signed URL, no auth required)
Route::get('email/confirm/{user}', function (\App\Models\User $user) {
    $user->update(['email_confirmed' => true]);

    return redirect()->route('home')->with('status', 'Email confirmed successfully.');
})->name('email.confirm')->middleware('signed');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::inertia('dashboard', 'dashboard')->name('dashboard');

    // Convention routes
    Route::get('conventions', [ConventionController::class, 'index'])->name('conventions.index');
    Route::get('conventions/create', [ConventionController::class, 'create'])->name('conventions.create');
    Route::post('conventions', [ConventionController::class, 'store'])->name('conventions.store');

    Route::middleware([EnsureConventionAccess::class, ScopeByRole::class])->group(function () {
        Route::get('conventions/{convention}', [ConventionController::class, 'show'])->name('conventions.show');
        Route::put('conventions/{convention}', [ConventionController::class, 'update'])->name('conventions.update');
    });

    Route::middleware([EnsureConventionAccess::class, EnsureOwnerRole::class])->group(function () {
        Route::delete('conventions/{convention}', [ConventionController::class, 'destroy'])->name('conventions.destroy');
        Route::get('conventions/{convention}/export', [ConventionController::class, 'export'])->name('conventions.export');
    });

    // Floor routes (nested under conventions)
    Route::middleware([EnsureConventionAccess::class, ScopeByRole::class])->group(function () {
        Route::get('conventions/{convention}/floors', [FloorController::class, 'index'])->name('floors.index');
        Route::post('conventions/{convention}/floors', [FloorController::class, 'store'])->name('floors.store');
    });

    Route::put('floors/{floor}', [FloorController::class, 'update'])->name('floors.update');
    Route::delete('floors/{floor}', [FloorController::class, 'destroy'])->name('floors.destroy');

    // Section routes (nested under conventions/floors for index and store)
    Route::middleware([EnsureConventionAccess::class, ScopeByRole::class])->group(function () {
        Route::get('conventions/{convention}/floors/{floor}/sections', [SectionController::class, 'index'])->name('sections.index');
        Route::post('conventions/{convention}/floors/{floor}/sections', [SectionController::class, 'store'])->name('sections.store');
    });

    // Section routes (standalone by section ID)
    Route::get('sections/{section}', [SectionController::class, 'show'])->name('sections.show');
    Route::put('sections/{section}', [SectionController::class, 'update'])->name('sections.update');
    Route::patch('sections/{section}/occupancy', [SectionController::class, 'updateOccupancy'])->name('sections.updateOccupancy');
    Route::post('sections/{section}/full', [SectionController::class, 'setFull'])->name('sections.setFull');
    Route::delete('sections/{section}', [SectionController::class, 'destroy'])->name('sections.destroy');

    // User routes (nested under conventions)
    Route::middleware([EnsureConventionAccess::class, ScopeByRole::class])->group(function () {
        Route::get('conventions/{convention}/users', [UserController::class, 'index'])->name('users.index');
        Route::post('conventions/{convention}/users', [UserController::class, 'store'])->name('users.store');
        Route::put('conventions/{convention}/users/{user}', [UserController::class, 'update'])->name('users.update');
        Route::delete('conventions/{convention}/users/{user}', [UserController::class, 'destroy'])->name('users.destroy');
    });

    // Resend invitation with throttle (3 per 60 minutes)
    Route::middleware([EnsureConventionAccess::class, 'throttle:3,60'])->group(function () {
        Route::post('conventions/{convention}/users/{user}/resend-invitation', [UserController::class, 'resendInvitation'])->name('users.resendInvitation');
    });

    // Attendance routes
    Route::middleware([EnsureConventionAccess::class])->group(function () {
        Route::post('conventions/{convention}/attendance/start', [AttendanceController::class, 'start'])->name('attendance.start');
        Route::post('conventions/{convention}/attendance/{attendancePeriod}/stop', [AttendanceController::class, 'stop'])->name('attendance.stop');
    });

    Route::post('sections/{section}/attendance/{attendancePeriod}/report', [AttendanceController::class, 'report'])->name('attendance.report');

    // Search routes (accessible to all authenticated users with convention access, no role-based filtering)
    Route::middleware([EnsureConventionAccess::class])->group(function () {
        Route::get('conventions/{convention}/search', [SearchController::class, 'index'])->name('search.index');
    });
});

require __DIR__.'/settings.php';
