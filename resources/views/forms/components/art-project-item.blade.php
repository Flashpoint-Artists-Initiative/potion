@use(\App\Enums\GrantFundingStatusEnum)

<x-dynamic-component
    :component="$getFieldWrapperView()"
    :field="$field"
>
    <x-filament::fieldset 
        x-data="{ count: $wire.$entangle('{{ $getStatePath() }}') }"
        class="pt-2 hover:bg-custom-400/10 cursor-pointer"
        style="--c-400:var(--primary-400)"
        wire:click.stop="mountAction('openModal', {'id': {{ $getRecord()->id }}})"
    >
        <x-slot name="label">
            <span class="text-2xl">{{ $getRecord()->name }}</span>
            <span class="dark:bg-gray-950 py-1 rounded-md">
                <x-filament::badge class="inline-grid" :color="$getRecord()->funding_status->getColor()">
                    {{ $getRecord()->funding_status->getLabel() }}
                </x-filament::badge>
            </span>
        </x-slot>
        <div
            class="art-project md:flex items-center cursor-pointer" 
        >
            <div class="cursor-pointer flex justify-center" style="width: 200px; height: 200px;">
                <img class="rounded-md" src="{{ $getRecord()->getFirstMediaUrl(conversionName: 'preview') }}">
            </div>
            <div class="flex-1 mx-3">
                <p><span class="font-bold">Artist:</span> {{ $getRecord()->artist_name }}</p>
                <p>{{ str($getRecord()->description)->limit(200, preserveWords: true) }}</p>
                <div class="flex flex-col gap-2">
                <p><span class="font-bold">Minimum Funding Requested:</span> ${{ $getRecord()->min_funding }}</p>
                <p><span class="font-bold">Maximum Funding Requested:</span> ${{ $getRecord()->max_funding }}</p>
                </div>
                {{ $this->openModal }}
            </div>
            @if (!$getDisableVoting() && $getRecord()->fundingStatus != GrantFundingStatusEnum::MaxReached)
            <div>
                <x-counter-input />
            </div>  
            @endif
        </div>
    </x-filament::fieldset>
</x-dynamic-component>