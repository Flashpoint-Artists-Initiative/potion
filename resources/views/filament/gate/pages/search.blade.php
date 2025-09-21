<x-filament-panels::page>
    <form wire:submit="submit">
        {{ $this->form }}
    </form>

    {{ $this->searchHistoryInfolist }}
</x-filament-panels::page>
