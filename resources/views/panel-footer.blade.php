@php
    use Filament\Support\Enums\ActionSize;
    use Filament\Support\Enums\FontWeight;
    use Filament\Support\Enums\IconPosition;
    use Filament\Support\Enums\IconSize;
@endphp

<div class="text-center">
    <div>
        <x-filament::link
            href="mailto:{{ config('mail.from.address') }}"
            size="large">
            Contact Us
        </x-filament::link>
    </div>
    <div>
        <x-filament::link
            href="{{ route('privacy-policy') }}">
            Privacy Policy
        </x-filament::link>
        ‒
        <x-filament::link
            href="{{ route('terms-of-service') }}">
            Terms of Service
        </x-filament::link>
    </div>
</div>
