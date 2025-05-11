@assets
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/baguettebox.js/1.12.0/baguetteBox.css">
<script src="https://cdnjs.cloudflare.com/ajax/libs/baguettebox.js/1.12.0/baguetteBox.js" async></script>
@endassets
<x-filament-panels::page>
    <div x-data="{
        totalCount: 0,
        max: {{ $maxVotes }},
        remaining: {{ $maxVotes }},
        hasVoted: {{ $hasVoted ? 'true' : 'false' }},
        votingIsOpen: {{ $votingIsOpen ? 'true' : 'false' }},

        init() {
            this.remaining = this.max;
            $watch('totalCount', (value) => {
                this.remaining = this.max - value;
            });
        }
    }"
    x-init="init()"
    class="art-grants-page"
    >
        @if (!$hasVoted && $votingIsOpen)
            @if ($artGrantContent)
                <div class="prose dark:prose-invert max-w-none">
                    {!! str($artGrantContent)->sanitizeHtml() !!}
                </div>
            @endif
        @elseif ($hasVoted && $votingIsOpen)
            <p class="pb-4">You've already voted, but you can still check out all the projects!</p>
        @else
            <p class="pb-4">Voting has ended, the projects below have all been funded!</p>
        @endif
        <x-filament-panels::form wire:submit="submitVotes">
            @if (!$hasVoted && $votingIsOpen)
            <span class="dark:bg-gray-950 sticky grid z-10" style="top: 4rem" >
                <x-filament::badge class="my-2" x-show="remaining > 0">
                    <p class="text-2xl"> VOTES REMAINING: <span x-text="remaining"></span></p>
                </x-filament::badge>
                <x-filament::button class="flex my-2" type="submit" x-show="remaining == 0" style="height: 2.9em">
                    Submit Votes
                </x-filament::button>
            </span>
            @endif
            {{ $this->form }}
            @if (!$hasVoted && $votingIsOpen)
                <x-filament::button type="submit" x-bind:disabled="remaining > 0" x-bind:class="remaining > 0 && 'opacity-50'">
                    <span x-show="remaining == 0">Submit Votes</span>
                    <span x-show="remaining > 0">Allocate your <span x-text="remaining"></span> remaining votes before submitting</span>
                </x-filament::button>
            @endif
        </x-filament-panels::form>
    </div>
<x-filament-actions::modals />
</x-filament-panels::page>
