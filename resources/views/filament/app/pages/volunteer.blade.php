<x-filament-panels::page>
    @if (! $signupsEnabled)
    <div>
        <h1 class="text-2xl text-center">Volunteer signups are closed</h1>
        @if (now()->lessThan($signupStartDate))
            <p class="text-center">Signups will open on {{ $signupStartDate->format('D F jS, Y g:i A T') }}</p>
        @endif
    </div>
    @else
        <x-filament-panels::form>
            {{ $this->form }}
        </x-filament-panels::form>
        {{ $this->table }}
    @endif
</x-filament-panels::page>
