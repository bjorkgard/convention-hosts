<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Vite;
use Symfony\Component\HttpFoundation\Response;

class SecureHeaders
{
    /**
     * Security headers applied to all web responses.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        $response->headers->set('X-Content-Type-Options', 'nosniff');
        $response->headers->set('X-Frame-Options', 'SAMEORIGIN');
        $response->headers->set('X-XSS-Protection', '1; mode=block');
        $response->headers->set('Referrer-Policy', 'strict-origin-when-cross-origin');
        $response->headers->set('Strict-Transport-Security', 'max-age=31536000; includeSubDomains');
        $response->headers->set('Content-Security-Policy', $this->buildCsp());

        return $response;
    }

    /**
     * Build the Content-Security-Policy header value.
     */
    private function buildCsp(): string
    {
        $nonce = Vite::cspNonce();

        $directives = [
            'default-src' => ["'self'"],
            'script-src' => ["'self'", "'nonce-{$nonce}'"],
            'style-src' => ["'self'", "'unsafe-inline'"],
            'img-src' => ["'self'", 'data:'],
            'font-src' => ["'self'"],
            'connect-src' => ["'self'"],
            'object-src' => ["'none'"],
            'base-uri' => ["'self'"],
            'form-action' => ["'self'"],
        ];

        if ($this->isLocal()) {
            $viteHost = $this->viteDevUrl();

            $directives['script-src'][] = $viteHost;
            $directives['style-src'][] = $viteHost;
            $directives['connect-src'][] = $viteHost;
            $directives['connect-src'][] = str_replace('http', 'ws', $viteHost);
        }

        return collect($directives)
            ->map(fn (array $values, string $key) => $key.' '.implode(' ', $values))
            ->implode('; ');
    }

    /**
     * Determine if the application is running in local/development mode.
     */
    private function isLocal(): bool
    {
        return app()->environment('local', 'testing');
    }

    /**
     * Get the Vite dev server origin URL.
     */
    private function viteDevUrl(): string
    {
        return 'http://localhost:5173';
    }
}
