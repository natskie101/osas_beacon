<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Baseline security headers on every response.
 *
 * HSTS is only sent for HTTPS requests so local http:// development is never
 * pinned. No full Content-Security-Policy is applied yet — the portal relies
 * on inline scripts and styles (rich-text editor, carousels) — but
 * frame-ancestors provides the clickjacking protection without affecting
 * script execution. X-Powered-By version disclosure is stripped.
 */
class SecurityHeaders
{
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        // trustProxies('*') is configured, so isSecure() is true behind
        // Render/Cloudflare's TLS terminator and false on local http://.
        if ($request->isSecure()) {
            $response->headers->set('Strict-Transport-Security', 'max-age=31536000; includeSubDomains');
        }

        $response->headers->set('X-Content-Type-Options', 'nosniff');
        $response->headers->set('Referrer-Policy', 'strict-origin-when-cross-origin');
        $response->headers->set('X-Frame-Options', 'SAMEORIGIN');
        $response->headers->set('Content-Security-Policy', "frame-ancestors 'self'");
        $response->headers->remove('X-Powered-By');

        return $response;
    }
}
