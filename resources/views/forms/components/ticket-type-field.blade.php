<x-dynamic-component
    :component="$getFieldWrapperView()"
    :field="$field"
>
    <div x-data="{
        count: $wire.$entangle('{{ $getStatePath() }}'),
        {{-- increment() {
            if (this.state < Math.min(4,{{ $getRecord()->remainingTicketCount }})) {
                this.state++;
            }
        },
        decrement() {
            if (this.state > 0) {
                this.state--;
            }
        } --}}
    }">
        <x-filament::section class="ticket-purchase-item">
            <div class="flex flex-col gap-3 px-6 py-4">
                <div class="flex items-center gap-3">
                    <div class="grid flex-1 gap-y-1">
                    <h3 class="text-base font-semibold leading-6 text-gray-950 dark:text-white">
                        {{ $getRecord()->name }}
                        @if ($getRecord()->addon)
                            <x-filament::badge style="display: inline-flex;">
                                Add-on
                            </x-filament::badge>
                        @endif
                    </h3>
                    <p class="overflow-hidden break-words text-sm text-gray-500 dark:text-gray-400">
                        {{ $getRecord()->description }}
                    </p>
                </div>
                <div>
                    <p class="text-xl bold text-center pb-4">${{ $getRecord()->price }}</p>
                    <div class="flex flex-row justify-end">
                        <x-counter-input />
                    </div>
                </div>
            </div>
        </x-filament::section>
    </div>
</x-dynamic-component>
