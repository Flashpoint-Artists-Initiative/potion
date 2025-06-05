<?php

declare(strict_types=1);

namespace App\Providers;

use App\Events\ArtProjectFundingStatusChange;
use App\Listeners\ArtProjectFundingStatusListener;
use App\Listeners\EmailVerifiedListener;
use Illuminate\Auth\Events\Registered;
use Illuminate\Auth\Events\Verified;
use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;
use Illuminate\Support\Facades\Event;

class EventServiceProvider extends ServiceProvider
{
    /**
     * The event to listener mappings for the application.
     *
     * @var array<class-string, array<int, class-string>>
     */
    protected $listen = [
        Verified::class => [
            EmailVerifiedListener::class,
        ],
        ArtProjectFundingStatusChange::class => [
            ArtProjectFundingStatusListener::class,
        ],
    ];

    // Observers are registered via attributes in the model classes
    protected $observers = [];

    /**
     * Register any events for your application.
     */
    public function boot(): void
    {
        //
    }

    /**
     * Determine if events and listeners should be automatically discovered.
     */
    public function shouldDiscoverEvents(): bool
    {
        return false;
    }
}
