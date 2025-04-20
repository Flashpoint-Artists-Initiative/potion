{{-- The blank lines around the outermost block are important for some reason --}}
@if (filled($events))

<div class="flex items-center">
        <span class="text-lg px-2">{{ $currentEvent }}</span>
        <x-filament::dropdown
            placement="bottom-start"
        >
            <x-slot name="trigger">
                <x-filament::button
                    {{-- icon="heroicon-s-calendar"
                    icon-position="after" --}}
                    color="primary"
                    outlined="false"
                    tooltip="Select a different event"
                    size="xs"
                >
                    Select
                </x-filament::button>
            </x-slot>

            <x-filament::dropdown.list>
                @foreach ($events as $id => $name)
                    <x-filament::dropdown.list.item
                        wire:click="updateEventId({{ $id }})"
                        x-on:click="toggle"
                    >
                        {{ "$name" }}
                    </x-filament::dropdown.list.item>
                @endforeach
            </x-filament::dropdown.list>
        </x-filament::dropdown>
</div>

@else

    <div />

@endif
