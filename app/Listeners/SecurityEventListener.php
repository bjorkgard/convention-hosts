<?php

namespace App\Listeners;

use Illuminate\Auth\Events\Failed;
use Illuminate\Support\Facades\Log;

class SecurityEventListener
{
    /**
     * Handle failed login attempts.
     */
    public function handleFailedLogin(Failed $event): void
    {
        Log::channel('security')->warning('Failed login attempt', [
            'event' => 'failed_login',
            'email' => $event->credentials['email'] ?? 'unknown',
            'ip' => request()->ip(),
            'url' => request()->fullUrl(),
            'user_agent' => request()->userAgent(),
        ]);
    }

    /**
     * Log authorization failure (403).
     */
    public static function logAuthorizationFailure(string $reason, int|string|null $userId = null): void
    {
        Log::channel('security')->warning('Authorization failure', [
            'event' => 'authorization_failure',
            'reason' => $reason,
            'user_id' => $userId,
            'ip' => request()->ip(),
            'url' => request()->fullUrl(),
            'method' => request()->method(),
            'user_agent' => request()->userAgent(),
        ]);
    }

    /**
     * Log invalid signed URL access.
     */
    public static function logInvalidSignature(string $reason): void
    {
        Log::channel('security')->warning('Invalid signed URL access', [
            'event' => 'invalid_signed_url',
            'reason' => $reason,
            'ip' => request()->ip(),
            'url' => request()->fullUrl(),
            'user_agent' => request()->userAgent(),
        ]);
    }

    /**
     * Log rate limit violation.
     */
    public static function logRateLimitViolation(int|string|null $userId = null): void
    {
        Log::channel('security')->warning('Rate limit exceeded', [
            'event' => 'rate_limit_exceeded',
            'user_id' => $userId,
            'ip' => request()->ip(),
            'url' => request()->fullUrl(),
            'method' => request()->method(),
            'user_agent' => request()->userAgent(),
        ]);
    }
}
