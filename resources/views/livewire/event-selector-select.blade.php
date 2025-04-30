{{-- The blank lines around the outermost block are important for some reason --}}
@if (filled($events) && $events->count() > 1)

<div class="flex items-center">
    <x-filament::input.wrapper>
        <x-filament::input.select wire:model.live="eventId">
            @foreach ($events as $id => $name)
                <option value="{{ $id }}">
                    {{ "$name" }}
                </option>
            @endforeach
        </x-filament::input.select>
    </x-filament::input.wrapper>
</div>

@elseif (filled($events) && $events->count() == 1)

<div class="flex items-center">
    {{ $events->first() }}
</div>

@else

    <div />

@endif
