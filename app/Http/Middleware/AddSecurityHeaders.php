<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Vite;
use Symfony\Component\HttpFoundation\Response;

class AddSecurityHeaders
{
    /**
     * Handle an incoming request.
     *
     * Runs globally (bootstrap/app.php), not only on the web group:
     * error pages, throttled responses, JSON endpoints and the health
     * probe carry the same hardening as regular pages.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        $response->headers->set('X-Content-Type-Options', 'nosniff');
        $response->headers->set('X-Frame-Options', 'DENY');
        $response->headers->set('Referrer-Policy', 'strict-origin-when-cross-origin');
        $response->headers->set('Permissions-Policy', 'camera=(), display-capture=(), geolocation=(), microphone=(), usb=()');
        $response->headers->set('Cross-Origin-Opener-Policy', 'same-origin');

        // HTTPS is terminated by the reverse proxy: once the browser has
        // seen the site over https, it must refuse to talk plain http to
        // it (anti-downgrade).
        if ($request->secure()) {
            $response->headers->set('Strict-Transport-Security', 'max-age=31536000; includeSubDomains');
        }

        if (! app()->environment('local')) {
            $response->headers->set('Content-Security-Policy', $this->contentSecurityPolicy());
        }

        return $response;
    }

    /**
     * The Content-Security-Policy: nothing runs or loads from anywhere
     * but the site itself, and scripts and styles must carry the
     * per-request nonce. Inertia is compatible by design: the page
     * payload is inert JSON, the built assets are same-origin, and the
     * Vite nonce covers every tag the Vite plugin generates plus the
     * two inline boot tags in the layout. The local environment is
     * exempt because the Vite hot client cannot carry nonces, which is
     * why the header is only sent outside of it.
     */
    private function contentSecurityPolicy(): string
    {
        $nonce = Vite::cspNonce();

        return implode('; ', [
            "default-src 'self'",
            "script-src 'self' 'nonce-{$nonce}'",
            "style-src 'self' 'nonce-{$nonce}'",
            "img-src 'self' data:",
            "font-src 'self'",
            "connect-src 'self'",
            "object-src 'none'",
            "base-uri 'self'",
            "form-action 'self'",
            "frame-ancestors 'none'",
        ]);
    }
}
