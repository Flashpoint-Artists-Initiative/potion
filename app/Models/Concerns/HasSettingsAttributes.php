<?php

declare(strict_types=1);

namespace App\Models\Concerns;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Support\Arr;

trait HasSettingsAttributes
{
    /**
     * A simple way to access nested settings attributes
     * 
     * @param string $key The key to access in the settings array, in dot notation
     * @param mixed $defaultValue The default value to return if the key does not exist
     */
    protected function getSetting(string $key, mixed $defaultValue = null): mixed
    {
        return Arr::dot($this->settings)[$key] ?? $defaultValue;
    }

    protected function dollarsPerVote(): Attribute
    {
        return Attribute::get(fn () => $this->getSetting('art.dollars_per_vote', 1.0));
    }

    protected function votingEnabled(): Attribute
    {
        return Attribute::get(fn () => $this->getSetting('art.voting_enabled', false));
    }

    protected function votesPerUser(): Attribute
    {
        return Attribute::get(fn () => $this->getSetting('art.votes_per_user', 10));
    }

    protected function votingEndDate(): Attribute
    {
        return Attribute::get(fn () => $this->getSetting('art.voting_end_date', now()->addMinute()));
    }

    protected function ticketsPerSale(): Attribute
    {
        return Attribute::get(fn () => $this->getSetting('ticketing.tickets_per_sale', config('app.cart_max_quantity')));
    }

    protected function ticketsLockdown(): Attribute
    {
        return Attribute::get(fn () => $this->getSetting('lockdown.tickets', false));
    }

    protected function grantsLockdown(): Attribute
    {
        return Attribute::get(fn () => $this->getSetting('lockdown.grants', false));
    }

    protected function volunteersLockdown(): Attribute
    {
        return Attribute::get(fn () => $this->getSetting('lockdown.volunteers', false));
    }
}
