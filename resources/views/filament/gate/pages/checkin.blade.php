<x-filament-panels::page>

{{ $this->userInfolist }}

<x-notification-banner color="{{$this->checklist['ticket']['color']}}" class="mb-2 grow">
    {{ $this->checklist['ticket']['message'] }}
</x-notification-banner>

@if (isset($this->checklist['multiple_tickets']))
<x-notification-banner color="{{$this->checklist['multiple_tickets']['color']}}" class="mb-2 grow">
    {{ $this->checklist['multiple_tickets']['message'] }}
</x-notification-banner>
@endif

<x-notification-banner color="{{$this->checklist['waiver']['color']}}" class="mb-2 grow">
    {{ $this->checklist['waiver']['message'] }}
</x-notification-banner>

@if ($this->signWaiverAction->isVisible())
    {{ $this->signWaiverAction }}
@endif

@if ($this->transferTicketsAction->isVisible())
    {{ $this->transferTicketsAction }}
@endif

@if ($this->checkInAction->isVisible())
{{ $this->checkInAction }}
@endif

@if  ($this->wristbandNumber)
    {{ $this->updateWristbandAction }}
@endif

</x-filament-panels::page>
