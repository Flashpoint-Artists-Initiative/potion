<?php

declare(strict_types=1);

namespace App\Rules;

use App\Models\Event;
use App\Models\Ticketing\ReservedTicket;
use App\Models\Ticketing\TicketType;
use App\Models\User;
use Closure;
use Illuminate\Contracts\Validation\DataAwareRule;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Support\Facades\Auth;

class TicketSaleRule implements DataAwareRule, ValidationRule
{
    /** @var array<string, mixed> */
    protected array $data = [];

    /**
     * Run the validation rule.
     *
     * @param  \Closure(string, ?string=): \Illuminate\Translation\PotentiallyTranslatedString  $fail
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        // $attribute format is `data.tickets.{id}` or `data.reserved.{id}`
        $id = collect(explode('.', $attribute))->last();
        $lastId = collect(array_keys($this->data['data']['tickets'] ?? []))->last();

        // Only check these on the last item so we don't show multiple errors
        if ($id == $lastId) {
            $totalTickets = array_sum($this->data['data']['tickets'] ?? []);
            $totalReserved = count(array_filter(
                $this->data['data']['reserved'] ?? [],
                fn ($reserved) => $reserved === true
            ));

            if ($totalTickets + $totalReserved === 0) {
                $fail('Must purchase at least one ticket');

                return;
            }
        }

        if (str_contains($attribute, 'tickets')) {
            $this->generalSaleValidation($attribute, $value, $fail);
        }

        if (str_contains($attribute, 'reserved')) {
            $this->reservedValidation($attribute, $value, $fail);
        }
    }

    protected function generalSaleValidation(string $attribute, mixed $value, Closure $fail): void
    {
        $id = collect(explode('.', $attribute))->last();

        // Make sure the total quantity < the configured max
        $sum = array_sum($this->data['data']['tickets']);
        $maxQuantity = Event::getCurrentEvent()?->ticketsPerSale;
        if ($sum > $maxQuantity) {
            $fail('The total number of tickets in the cart cannot be more than ' . $maxQuantity);

            return;
        }

        // Validate that the ticket type event id is correct
        /** @var TicketType $ticketType */
        $ticketType = TicketType::findOrFail($id);

        if ($ticketType->event_id != Event::getCurrentEventId()) {
            $fail('Ticket does not belong to the current event');

            return;
        }

        // Check if the remaining quantity is high enough
        $remaining = $ticketType->remainingTicketCount;
        if ($remaining < $value) {
            if ($remaining == 1) {
                $fail('There is only one remaining ticket of this type');
            } else {
                $fail('There are only ' . $remaining . ' remaining tickets of this type');
            }

            return;
        }

        // Ensure the ticket type is available to purchase
        if (! $ticketType->hasAvailable($value)) {
            $fail('This ticket type is sold out or unavailable for purchase');
        }
    }

    protected function reservedValidation(string $attribute, mixed $value, Closure $fail): void
    {
        $parts = explode('.', $attribute);
        $id = $parts[2];
        $reservedTicket = ReservedTicket::findOrFail($id);

        if (! $reservedTicket->can_be_purchased) {
            $fail('This reserved ticket is not available for purchase');

            return;
        }

        if ($reservedTicket->ticketType->event_id != Event::getCurrentEventId()) {
            $fail('Reserved ticket does not belong to the current event');

            return;
        }

        /** @var User $user */
        $user = Auth::user();

        if ($reservedTicket->user_id != $user->id) {
            $fail('Cannot find matching reserved ticket for this user');

            return;
        }
    }

    /** @param array<string, mixed> $data */
    public function setData(array $data): static
    {
        $this->data = $data;

        return $this;
    }
}
