<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Services\ApiLockdownService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\LockedHttpException;

class LockdownMiddleware
{
    protected ApiLockdownService $lockdownService;

    public function __construct()
    {
        $this->lockdownService = new ApiLockdownService;
    }

    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next, string $type = 'site'): Response
    {
        throw_unless(in_array($type, ApiLockdownService::lockdownTypes()));

        $status = $this->lockdownService->getLockdownStatus();

        // 'site' lockdown takes precedence
        if ($status[$type] || $status['site']) {
            throw new LockedHttpException('This endpoint is temporarily locked.');
        }

        return $next($request);
    }
}
