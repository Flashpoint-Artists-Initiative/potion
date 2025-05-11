@php
use \App\Models\Event;
use \App\Filament\App\Clusters\UserPages\Pages\Tickets;
use \App\Filament\App\Clusters\UserPages\Pages\TicketTransfers;

$eventName = Event::getCurrentEvent()?->name;
$eventName = $eventName ? "<span class=\"font-bold\">{$eventName}</span>" : 'this event';    
$maxTickets = Event::getCurrentEvent()?->ticketsPerSale ?? config('app.cart_max_quantity');
@endphp
<div class="prose dark:prose-invert max-w-none">
    <h3>A quick summary</h3>
    <ul>
        <li>Everyone who wants to attend the event needs to have their own POTION account and ticket.</li>
        <li>You must use your legal name and birthday when signing up for an account in order to enter the event.</li>
        <li>Giving someone else a printed out ticket will no longer work.</li>
        <li>You can buy multiple tickets and then transfer them to other people.</li>
        <li>Reserved tickets can be assigned to you and can be purchased at any time, but will expire on a set date.</li>
    </ul>
    <h3>1 Participant = 1 Account + 1 Ticket</h3>
    <p>In order to attend {!! $eventName !!}, you need to have a POTION account and a ticket on that account. If you are buying tickets for other people, they will need to create their own accounts. You can transfer the tickets to them via their email address.</p>
    
    <h3>Use your legal name and birthday when signing up</h3>
    <p>We use your legal name to verify your age and identity at the event.  Make sure to enter your legal name and birthday as shown on your Government ID when creating an account. Your legal name and birthday will only be visible to gate staff and necessary event leadership.</p>

    <h3>Purchasing Tickets</h3>
    <ul>
        <li>You can purchase up to {{ $maxTickets }} tickets at a time.</li>
        <li>Tickets are non-refundable.</li>
        <li>You may not resell tickets for more than face value</li>
        <li>Once you've purchased a ticket, your QR code is available in <x-filament::link size="normal" class="not-prose" href="{{ Tickets::getUrl() }}">your profile</x-filament::link>, just click the "Your QR Code" button in the top right. You can print it out or show it on your phone.</li>
    </ul>

    <h3>Transferring Tickets</h3>
    <ul>
        <li>You cannot give someone a printed our QR code from your account. It will not work, and will only cause confusion at the gate.</li>
        <li>You can transfer a ticket to someone else via their email address. They will need to create a POTION account and register for the event.</li>
        <li>To transfer a ticket, go to <x-filament::link size="normal" class="not-prose" href="{{ TicketTransfers::getUrl() }}">your profile</x-filament::link> and select the ticket you want to transfer. You can then enter the email address of the person you want to transfer it to. They will receive an email with instructions on how to accept the transfer.</li>
        <li>All tickets purchased in the general sale are transferable, but some reserved tickets may not be. If a ticket is not transferable, it will not have a transfer link next to it in your profile. A non-transferrable reserved ticket will not be transferrable after purchase.</li>
    </ul>
    <h3>Reserved Tickets</h3>
    <p>Reserved tickets are distributed outside of the main sale.  Previous volunteers, theme camp organizers, artists, and scholarship recipients may be eligible for reserved tickets. If you are eligible for a reserved ticket, you will receive an email with instructions on how to claim it.  If you think you're missing reserved tickets that you should've received, <x-filament::link size="normal" class="not-prose" href="mailto:{{ config('mail.from.address') }}">contact us</x-filament::link></p>
    <p>Reserved tickets are assigned to you and can be purchased at any time, but will expire on a set date. When you are assigned reserved tickets you will receive an email, and will see them in <x-filament::link size="normal" class="not-prose" href="{{ Tickets::getUrl() }}">your profile</x-filament::link>. You can purchase them at any time before the expiration date.</p>
    <p>Some reserved tickets are transferable, and can be sent to someone else the same way a purchased ticket can be. A transferred reserved ticket will still need to be purchased by the person receiving it.</p>
</div>