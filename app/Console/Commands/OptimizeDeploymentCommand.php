<?php

declare(strict_types=1);

namespace App\Console\Commands;

use Closure;
use Illuminate\Console\Command;

class OptimizeDeploymentCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'deploy';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Shortcut for common deployment tasks. Runs migrations, updates permissions, and builds caches.';

    /**
     * Execute the console command.
     */
    public function handle(): void
    {
        $this->components->info('Running deployment tasks...');

        $tasks = collect([
            'Migrating Database' => fn (): bool => $this->callSilent('migrate') === Command::SUCCESS,
            'Updating Permissions' => fn (): bool => $this->callSilent('permission:cache') === Command::SUCCESS,
        ]);

        $tasks->each(fn (Closure $task, string $description) => $this->components->task($description, $task));

        $this->call('optimize');

        $this->newLine();
    }
}
