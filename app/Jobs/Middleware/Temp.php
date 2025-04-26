<?php

namespace App\Jobs\Middleware;

use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Support\Facades\Log;

class Temp
{
    use Dispatchable;

    /**
     * Create a new job instance.
     */
    public function __construct()
    {
        //
    }

    /**
     * Execute the job.
     */
    public function handle($job, $next): void
    {
        Log::info(config('cache'));
        $next($job);
    }
}
