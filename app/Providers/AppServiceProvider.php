<?php

namespace App\Providers;

use App\Listeners\SecurityEventListener;
use Carbon\CarbonImmutable;
use Illuminate\Auth\Events\Failed;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\ServiceProvider;
use Illuminate\Validation\Rules\Password;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        $this->configureDefaults();
        $this->registerSecurityListeners();

        \App\Models\User::observe(\App\Observers\UserObserver::class);
    }

    /**
     * Configure default behaviors for production-ready applications.
     */
    protected function configureDefaults(): void
    {
        Date::use(CarbonImmutable::class);

        DB::prohibitDestructiveCommands(
            app()->isProduction(),
        );

        Password::defaults(fn (): ?Password => app()->isProduction()
            ? Password::min(12)
                ->mixedCase()
                ->letters()
                ->numbers()
                ->symbols()
                ->uncompromised()
            : null,
        );
    }

    /**
     * Register security event listeners for logging.
     */
    protected function registerSecurityListeners(): void
    {
        $listener = new SecurityEventListener;

        // Log failed login attempts
        Event::listen(Failed::class, [$listener, 'handleFailedLogin']);

        // Log rate limit violations (429 responses)
        Event::listen(\Illuminate\Foundation\Http\Events\RequestHandled::class, function ($event) {
            if ($event->response->getStatusCode() === 429) {
                SecurityEventListener::logRateLimitViolation(
                    $event->request->user()?->id,
                );
            }
        });
    }
}
