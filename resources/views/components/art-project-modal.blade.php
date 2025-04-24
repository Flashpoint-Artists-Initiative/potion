<div
    x-init="() => {
            baguetteBox.run('.gallery');
            {{-- Move the lightbox display here to prevent baguetteBox and livewire from fighting over focus --}}
            $el.appendChild(document.getElementById('baguetteBox-overlay'));
    }"
>
    <x-filament::badge class="inline-grid" :color="$project->funding_status->getColor()">
        {{ $project->funding_status->getLabel() }}
    </x-filament::badge>
    <p><span class="font-bold">Artist:</span> {{ $project->artist_name }}</p>
    <p>{{ $project->description }}</p>
    <div class="flex flex-col gap-2">
        <p><span class="font-bold">Minimum Funding Requested:</span> ${{ $project->min_funding }}</p>
        <p><span class="font-bold">Maximum Funding Requested:</span> ${{ $project->max_funding }}</p>
    </div>
    <div class="gallery flex gap-2 flex-wrap">
    @foreach ($project->getMedia() as $media)
        <a href="{{ $media->getUrl() }}">
            <div style="width: 100px; height: 100px;" class="flex justify-center overflow-y-auto">
                {{ $media('preview')->attributes(['class' => 'rounded-md']) }} 
            </div>
        </a>
    @endforeach
    </div>
</div>