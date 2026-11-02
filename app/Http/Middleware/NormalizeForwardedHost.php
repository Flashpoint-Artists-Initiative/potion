<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * API Gateway HTTP API invokes Lambda with Host set to the execute-api domain.
 * The public custom domain is sent in X-Forwarded-Host (via CloudFront / API GW).
 * Symfony trusted-host checks use Host, so we align Host before TrustHosts runs.
 */
class NormalizeForwardedHost
{
    /**
     * @param  Closure(Request): Response  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $forwardedHost = $request->header('X-Forwarded-Host');

        if (! is_string($forwardedHost) || $forwardedHost === '') {
            return $next($request);
        }

        $host = strtolower(trim(explode(',', $forwardedHost)[0]));
        $host = preg_replace('/:\d+$/', '', $host) ?? $host;

        if ($host === '') {
            return $next($request);
        }

        $request->headers->set('HOST', $host);
        $request->server->set('HTTP_HOST', $host);
        $request->server->set('SERVER_NAME', $host);

        return $next($request);
    }
}
