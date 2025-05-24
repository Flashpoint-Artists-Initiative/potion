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
     * @param mixed $default The default value to return if the key does not exist.  If null, it will check the config file for a default value.
     */
    protected function getSetting(string $key, mixed $default = null): mixed
    {
        $defaultValue = $default ?? config('app.defaults.' . $key, null);
        return Arr::dot($this->settings)[$key] ?? $defaultValue;
    }

    protected function dollarsPerVote(): Attribute
    {
        return Attribute::get(fn () => (int) $this->getSetting('art.dollars_per_vote'));
    }

    protected function votingEnabled(): Attribute
    {
        return Attribute::get(fn () => (bool) $this->getSetting('art.voting_enabled'));
    }

    protected function votesPerUser(): Attribute
    {
        return Attribute::get(fn () => (int) $this->getSetting('art.votes_per_user'));
    }

    protected function ticketsPerSale(): Attribute
    {
        return Attribute::get(fn () => $this->getSetting('ticketing.tickets_per_sale'));
    }

    protected function ticketsLockdown(): Attribute
    {
        return Attribute::get(fn () => $this->getSetting('lockdown.tickets'));
    }

    protected function grantsLockdown(): Attribute
    {
        return Attribute::get(fn () => $this->getSetting('lockdown.grants'));
    }

    protected function volunteersLockdown(): Attribute
    {
        return Attribute::get(fn () => $this->getSetting('lockdown.volunteers'));
    }

    protected function volunteerSignupsStart(): Attribute
    {
        return Attribute::get(fn () => $this->getSetting('volunteering.signups_start'));
    }

    protected function volunteerSignupsEnd(): Attribute
    {
        return Attribute::get(fn () => $this->getSetting('volunteering.signups_end'));
    }
}
