<?php

namespace App\Support\Consent;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cookie;
use Symfony\Component\HttpFoundation\Response;

class OptionalStorageRegistry
{
    /**
     * @var list<string>
     */
    private const OPTIONAL_COOKIE_NAMES = [
        'appearance',
        'theme',
        'sidebar_state',
    ];

    /**
     * @var list<string>
     */
    private const OPTIONAL_LOCAL_STORAGE_KEYS = [
        'appearance',
        'theme',
        'install-prompt-dismissed',
    ];

    /**
     * @var list<string>
     */
    private const ALLOWED_APPEARANCE_VALUES = [
        'light',
        'dark',
        'system',
    ];

    /**
     * @var list<string>
     */
    private const ALLOWED_THEME_VALUES = [
        'default',
        'ocean',
        'forest',
        'sunset',
        'rose',
        'apple',
        'android',
    ];

    public function __construct(
        private readonly UserConsentResolver $consentResolver,
    ) {}

    public function allowsOptionalStorage(?User $user): bool
    {
        return $this->consentResolver->resolve($user)['allowOptionalStorage'] === true;
    }

    /**
     * @return list<string>
     */
    public function optionalCookieNames(): array
    {
        return self::OPTIONAL_COOKIE_NAMES;
    }

    /**
     * @return list<string>
     */
    public function optionalLocalStorageKeys(): array
    {
        return self::OPTIONAL_LOCAL_STORAGE_KEYS;
    }

    public function fallbackAppearance(): string
    {
        return 'system';
    }

    public function fallbackTheme(): string
    {
        return 'default';
    }

    public function fallbackSidebarOpen(): bool
    {
        return true;
    }

    public function trustedAppearance(Request $request): string
    {
        if (! $this->allowsOptionalStorage($request->user())) {
            return $this->fallbackAppearance();
        }

        $appearance = $request->cookie('appearance');

        return in_array($appearance, self::ALLOWED_APPEARANCE_VALUES, true)
            ? $appearance
            : $this->fallbackAppearance();
    }

    public function trustedTheme(Request $request): string
    {
        if (! $this->allowsOptionalStorage($request->user())) {
            return $this->fallbackTheme();
        }

        $theme = $request->cookie('theme');

        return in_array($theme, self::ALLOWED_THEME_VALUES, true)
            ? $theme
            : $this->fallbackTheme();
    }

    public function trustedSidebarOpen(Request $request): bool
    {
        if (! $this->allowsOptionalStorage($request->user())) {
            return $this->fallbackSidebarOpen();
        }

        if (! $request->hasCookie('sidebar_state')) {
            return $this->fallbackSidebarOpen();
        }

        return $request->cookie('sidebar_state') === 'true';
    }

    public function enforceOptionalCookiePolicy(Request $request, Response $response): Response
    {
        if ($this->allowsOptionalStorage($request->user())) {
            return $response;
        }

        return $this->forgetOptionalCookies($response);
    }

    public function forgetOptionalCookies(Response $response): Response
    {
        foreach ($this->optionalCookieNames() as $cookieName) {
            $response->headers->setCookie(Cookie::forget($cookieName));
        }

        return $response;
    }
}
