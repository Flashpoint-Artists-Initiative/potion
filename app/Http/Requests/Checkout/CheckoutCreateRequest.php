<?php

declare(strict_types=1);

namespace App\Http\Requests\Checkout;

use App\Models\Ticketing\ReservedTicket;
use App\Models\Ticketing\TicketType;
use App\Models\User;
use Auth;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class CheckoutCreateRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'tickets' => 'array',
            'tickets.*.id' => ['required', 'distinct', 'exists:ticket_types'],
            'tickets.*.quantity' => ['required', 'integer'],
            'reserved' => ['array'],
            'reserved.*' => ['required', 'distinct', 'integer', 'exists:reserved_tickets,id'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        if ($validator->errors()->count() > 0) {
            return;
        }

        $this->combinedValidation($validator);
        $this->generalSaleValidation($validator);
        $this->reservedValidation($validator);
    }

    protected function combinedValidation(Validator $validator): void
    {
        $validator->after(function (Validator $validator) {
            if (array_sum($this->input('tickets.*.quantity', [])) + count($this->input('reserved', [])) === 0) {
                $validator->errors()->add('general', 'Must purchase at least one ticket');
            }
        });
    }

    protected function generalSaleValidation(Validator $validator): void
    {
        if (count($this->input('tickets', [])) === 0) {
            return;
        }

        $validator->after(function (Validator $validator) {
            // Make sure the total quantity < the configured max
            $quantities = $this->input('tickets.*.quantity', []);
            $sum = array_sum($quantities);
            if ($sum > (int) config('app.defaults.ticketing.tickets_per_sale')) {
                $validator->errors()->add('tickets', 'The total number of tickets in the cart cannot be more than ' . config('app.defaults.ticketing.tickets_per_sale'));

                return;
            }

            // Validate that the ticket type event ids are all the same
            $ids = $this->input('tickets.*.id');
            $ticketTypes = TicketType::findMany($ids);
            $eventIds = $ticketTypes->pluck('event_id')->toArray();
            if (count(array_unique($eventIds)) > 1) {
                $validator->errors()->add('tickets', 'All ticket types must belong to the same event');
            }

            // Attach the event_id to the request for easy reference later
            if (count($eventIds) > 0) {
                $this->merge(['event_id' => $eventIds[0]]);
            }

            // Ensure each ticket type is available to purchase
            foreach ($this->input('tickets') as $key => $values) {
                /** @var TicketType $ticketType */
                $ticketType = $ticketTypes->find($values['id']);

                if (! $ticketType->hasAvailable($values['quantity'])) {
                    $validator->errors()->add("tickets.$key", 'This ticket type is sold out or unavailable for purchase');
                }
            }
        });
    }

    protected function reservedValidation(Validator $validator): void
    {
        if (count($this->input('reserved', [])) === 0) {
            return;
        }

        $validator->after(function (Validator $validator) {
            /** @var User $user */
            $user = Auth::user();
            $user->load('reservedTickets.ticketType');

            // Get valid reserved tickets
            $reservedTickets = $user->reservedTickets->filter(function (ReservedTicket $value) {
                return $value->can_be_purchased;
            });

            // Validate that the reserved ticket event ids are all the same
            $eventIds = $reservedTickets->pluck('ticketType.event_id')->toArray();
            if (count(array_unique($eventIds)) > 1) {
                $validator->errors()->add('tickets', 'All reserved tickets must belong to the same event');
            }

            // Reserved ticket event must match general sale ticket event
            if (count($eventIds) > 0) {
                if ($this->input('event_id')) {
                    if ($this->input('event_id') !== $eventIds[0]) {
                        $validator->errors()->add('reserved', 'Reserved tickets must belong to the same event as general sale tickets');
                    }
                } else {
                    $this->merge(['event_id' => $eventIds[0]]);
                }
            }

            // Make sure reserved ticket is found for the current user
            foreach ($this->input('reserved') as $key => $id) {
                $matchingTicket = $reservedTickets->find($id);
                if (is_null($matchingTicket)) {
                    $validator->errors()->add("reseved.$key", 'Cannot find matching reserved ticket for this user.');
                }
            }
        });
    }
}
