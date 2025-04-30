<x-filament-panels::page>
    <p>
        Lockdown is used to globally make the site read-only.  
        When it is enabled, no user can make any changes to their account, purchase or transfer tickets, or signup for volunteer shifts.
        This should only be used just before an event's data is copied over for offline use.
    </p>
    <p>
        Once the event is over, you should disable lockdown to allow users to make changes to their accounts again.
        Data collected during the event can be imported back into the system with [TBD].
    </p>
    <x-filament-panels::form wire:submit="submit">
        {{ $this->form }}
        <x-filament::button type="submit" class="max-w-fit">
            Save
        </x-filament::button>
    </x-filament-panels::form>
</x-filament-panels::page>
