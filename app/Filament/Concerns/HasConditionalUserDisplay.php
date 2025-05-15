<?php

declare(strict_types=1);

namespace App\Filament\Concerns;

use App\Filament\Admin\Resources\UserResource;
use Closure;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Support\Facades\Auth;

trait HasConditionalUserDisplay
{
    protected string|Closure $userPage = 'view';

    public function userPage(string|Closure $page): static
    {
        $this->userPage = $page;

        return $this;
    }

    public function getUserPage(): string
    {
        return (string) $this->evaluate($this->userPage);
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this->color($this->checkAccess('primary'))
            ->iconColor($this->checkAccess('primary'))
            ->icon('heroicon-m-user')
            ->url($this->checkAccess(
                fn ($record) => UserResource::getUrl($this->getUserPage(), ['record' => $record->user->id])
            ))
            ->formatStateUsing(fn ($record) => $record->user->display_name);
    }

    protected function checkAccess(string|Htmlable|Closure|null $hasAccess, string|Htmlable|Closure|null $noAccess = null): Closure
    {
        return function () use ($hasAccess, $noAccess) {
            if (Auth::user()?->can('users.view')) {
                return $this->evaluate($hasAccess);
            }

            return $this->evaluate($noAccess);
        };
    }
}
