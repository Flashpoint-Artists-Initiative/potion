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
        @if (!$hasVoted)
            
        <p>[Insert Fluff Here].  Click a project to view more details.  
            Use the buttons to the right of each project to allocate your votes, 
            then hit the submit button at the bottom of the page.
        </p>
        @else
        <p class="pb-4">You've already voted, but you can still check out all the projects!</p>
        @endif
        <x-filament-panels::form wire:submit="submitVotes">
            @if (!$hasVoted)
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
            @if (!$hasVoted)
                <x-filament::button type="submit">
                    Submit Votes
                </x-filament::button>
            @endif
        </x-filament-panels::form>
    </div>
<x-filament-actions::modals />
</x-filament-panels::page>
