<?php

declare(strict_types=1);

namespace Tests\Feature\Api\StripeWebhook;

use Closure;
use Illuminate\Http\Request;
use Stripe\Event;
use Symfony\Component\HttpFoundation\Response;

class StripeWebhookTestMiddleware
{
    public function __construct(public Event $event) {}

    /**
     * Verify that the request is coming from the stripe API
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $request->merge(['event' => $this->event]);

        return $next($request);
    }
}
