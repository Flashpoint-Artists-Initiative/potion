<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Models\Ticketing\PurchasedTicket;
use App\Models\Ticketing\ReservedTicket;
use App\Models\User;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class TicketTransferCreateRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'purchased_tickets' => ['array'],
            'purchased_tickets.*' => ['distinct', 'integer', 'exists:purchased_tickets,id'],
            'reserved_tickets' => ['array'],
            'reserved_tickets.*' => ['distinct', 'integer', 'exists:reserved_tickets,id'],
            'email' => ['required', 'email'],
        ];
    }

    /**
     * Runs before validation
     *
     * Converts purchased and reserved ticket input to arrays
     */
    protected function prepareForValidation(): void
    {
        if (! is_null($this->purchased_tickets) && ! is_array($this->purchased_tickets)) {
            $this->merge(['purchased_tickets' => [$this->purchased_tickets]]);
        }

        if (! is_null($this->reserved_tickets) && ! is_array($this->reserved_tickets)) {
            $this->merge(['reserved_tickets' => [$this->reserved_tickets]]);
        }
    }

    public function getTransferUser(): User
    {
        $user = auth()->user();

        abort_unless($user instanceof User, 400, 'Invalid request');

        return $user;
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator) {
            if (! $this->purchased_tickets && ! $this->reserved_tickets) {
                $validator->errors()->add('tickets', 'At least one ticket must be included in the transfer');

                return;
            }

            $user = $this->getTransferUser();
            $user->load(['reservedTickets.ticketType', 'reservedTickets.purchasedTicket', 'purchasedTickets.ticketType']);

            $count = 0;

            // Validate each reserved ticket
            foreach ($this->input('reserved_tickets', []) as $key => $id) {
                $success = true;
                /** @var ?ReservedTicket $matchingTicket */
                $matchingTicket = $user->reservedTickets->find($id);

                if (is_null($matchingTicket)) {
                    $validator->errors()->add("reserved_tickets.$key", 'Cannot find valid reserved ticket for this user.');

                    continue;
                }

                if (! $matchingTicket->ticketType->transferable) {
                    $validator->errors()->add("reserved_tickets.$key", 'Tickets of this type cannot be transfered. Contact support for assistance.');
                    $success = false;
                }

                if (! $matchingTicket->can_be_purchased) {
                    $validator->errors()->add("reserved_tickets.$key", 'Unpurchaseable tickets cannot be transfered.');
                    $success = false;
                }

                if ($matchingTicket->is_purchased) {
                    $validator->errors()->add("reserved_tickets.$key", 'Reserved tickets that have been purchased cannot be transfered.');
                    $success = false;
                }

                if ($matchingTicket->has_active_transfer) {
                    $validator->errors()->add("reserved_tickets.$key", 'This ticket is already being transfered.  Cancel the existing transfer first.');
                    $success = false;
                }

                if ($success) {
                    $count++;
                }
            }

            // Validate each purchased ticket
            foreach ($this->input('purchased_tickets', []) as $key => $id) {
                $success = true;
                /** @var ?PurchasedTicket $matchingTicket */
                $matchingTicket = $user->purchasedTickets->find($id);

                if (is_null($matchingTicket)) {
                    $validator->errors()->add("purchased_tickets.$key", 'Cannot find purchased ticket for this user.');

                    continue;
                }

                if (! $matchingTicket->ticketType->transferable) {
                    $validator->errors()->add("purchased_tickets.$key", 'Tickets of this type cannot be transfered. Contact support for assistance.');
                    $success = false;
                }

                if ($matchingTicket->has_active_transfer) {
                    $validator->errors()->add("purchased_tickets.$key", 'This ticket is already being transfered.  Cancel the existing transfer first.');
                    $success = false;
                }

                if ($success) {
                    $count++;
                }
            }

            if ($count === 0) {
                $validator->errors()->add('tickets', 'At least one ticket must be included in the transfer');
            }
        });
    }
}
