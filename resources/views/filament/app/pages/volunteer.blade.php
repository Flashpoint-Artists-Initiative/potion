<x-filament-panels::page>
    @if ( ! $hasTicket )
        <x-notification-banner color="warning" class="mb-2 grow">
            You do not have a ticket for this event, so you cannot sign up to volunteer. Please purchase a ticket first.
        </x-notification-banner>
    @endif
    @if (! $signupsEnabled)
    <div>
        <h1 class="text-2xl text-center">Volunteer signups are closed</h1>
        @if (now()->lessThan($signupStartDate))
            <p class="text-center">Signups will open on {{ $signupStartDate->format('D F jS, Y g:i A T') }}</p>
        @endif
    </div>
    @else
        @if ($teamId)
            {{ $this->shiftTypesInfolist }}

            <x-filament-panels::form>
                {{ $this->form }}
            </x-filament-panels::form>

            {{ $this->table }}
        @else
            @if (\App\Filament\App\Widgets\UserShifts::canView())
                @livewire(\App\Filament\App\Widgets\UserShifts::class)
            @endif

            {{ $this->teamsInfolist }}

        @endif
    @endif
</x-filament-panels::page>
