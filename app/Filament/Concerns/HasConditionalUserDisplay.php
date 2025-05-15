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

    protected string|Closure $userRelation = 'user';

    public function userPage(string|Closure $page): static
    {
        $this->userPage = $page;

        return $this;
    }

    public function getUserPage(): string
    {
        return (string) $this->evaluate($this->userPage);
    }

    public function userRelation(string|Closure $relation): static
    {
        $this->userRelation = $relation;

        return $this;
    }

    public function getUserRelation(): string
    {
        return (string) $this->evaluate($this->userRelation);
    }

    public static function make(string $name): static
    {
        return parent::make($name . '.display_name')->userRelation($name);
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this->color($this->checkAccess('primary'))
            ->iconColor($this->checkAccess('primary'))
            ->icon('heroicon-m-user')
            ->url($this->checkAccess(
                function ($record) {
                    if ($record->{$this->getUserRelation()}) {
                        return UserResource::getUrl($this->getUserPage(), ['record' => $record->{$this->getUserRelation()}->id]);
                    }

                    return null;
                }
            ))
            ->formatStateUsing(fn ($record) => $record->{$this->getUserRelation()}->display_name);

        $this->additionalSetUp();
    }

    protected function additionalSetUp(): void {}

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
