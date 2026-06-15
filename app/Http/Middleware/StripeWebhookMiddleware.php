<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Stripe\Webhook;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\HttpException;

class StripeWebhookMiddleware
{
    /**
     * Verify that the request is coming from the stripe API
     *
     * @param  Closure(Request): (Response)  $next
     *
     * @codeCoverageIgnore
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Verify signature
        $signature = $request->header('Stripe-Signature');
        $secret = config('services.stripe.webhook_secret');

        abort_if(is_null($signature), 400, 'Invalid request');

        $event = Webhook::constructEvent($request->getContent(), $signature, $secret);
        $request->merge(['event' => $event]);

        // Verify incoming IP
        $ip = $request->ip();
        if (! in_array($ip, config('services.stripe.webhook_ips.WEBHOOKS'))) {
            throw new HttpException(400, 'Invalid request');
        }

        return $next($request);
    }
}
