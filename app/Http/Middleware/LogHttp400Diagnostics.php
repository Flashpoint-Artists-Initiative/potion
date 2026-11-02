<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Request as SymfonyRequest;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

class LogHttp400Diagnostics
{
    /**
     * @param  Closure(Request): Response  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (! config('logging.http_400_diagnostics')) {
            return $next($request);
        }

        try {
            $response = $next($request);
        } catch (Throwable $e) {
            self::log($request, null, $e);

            throw $e;
        }

        if ($response->getStatusCode() === 400) {
            self::log($request, $response, null);
        }

        return $response;
    }

    public static function log(Request $request, ?Response $response, ?Throwable $exception): void
    {
        if (! config('logging.http_400_diagnostics')) {
            return;
        }

        $context = self::requestContext($request);

        if ($response !== null) {
            $content = $response->getContent();
            $context['response'] = [
                'content_type' => $response->headers->get('Content-Type'),
                'body_preview' => is_string($content) ? mb_substr($content, 0, 500) : null,
            ];
        }

        if ($exception !== null) {
            $context['exception'] = self::exceptionChain($exception);
        }

        Log::warning('HTTP 400 diagnostics', $context);
    }

    /**
     * @return array<string, mixed>
     */
    public static function requestContext(Request $request): array
    {
        $resolvedHost = null;
        $hostResolutionError = null;

        try {
            $resolvedHost = $request->getHost();
        } catch (Throwable $e) {
            $hostResolutionError = self::exceptionChain($e);
        }

        return [
            'app' => [
                'env' => config('app.env'),
                'url' => config('app.url'),
            ],
            'request' => [
                'method' => $request->method(),
                'path' => $request->path(),
                'full_url' => $request->fullUrl(),
                'resolved_host' => $resolvedHost,
                'host_resolution_error' => $hostResolutionError,
            ],
            'headers' => [
                'host' => $request->header('Host'),
                'x_forwarded_host' => $request->header('X-Forwarded-Host'),
                'x_forwarded_proto' => $request->header('X-Forwarded-Proto'),
                'x_forwarded_for' => $request->header('X-Forwarded-For'),
                'x_forwarded_port' => $request->header('X-Forwarded-Port'),
            ],
            'server' => [
                'http_host' => $request->server->get('HTTP_HOST'),
                'server_name' => $request->server->get('SERVER_NAME'),
                'request_uri' => $request->server->get('REQUEST_URI'),
                'remote_addr' => $request->server->get('REMOTE_ADDR'),
            ],
            'symfony_trusted' => [
                'host_patterns' => SymfonyRequest::getTrustedHosts(),
                'proxies' => SymfonyRequest::getTrustedProxies(),
                'proxy_header_set' => SymfonyRequest::getTrustedHeaderSet(),
            ],
            'lambda' => [
                'request_id' => $request->server->get('HTTP_X_REQUEST_ID'),
            ],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private static function exceptionChain(Throwable $exception): array
    {
        $chain = [];
        $current = $exception;

        while ($current !== null) {
            $chain[] = [
                'class' => $current::class,
                'message' => $current->getMessage(),
                'file' => $current->getFile(),
                'line' => $current->getLine(),
            ];
            $current = $current->getPrevious();
        }

        return ['chain' => $chain];
    }
}
