<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\Events;

use App\Http\Controllers\OrionRelationsController;
use App\Models\Ticketing\PurchasedTicket;
use App\Models\Ticketing\TicketType;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\Relation;
use Orion\Http\Requests\Request;

class PurchasedTicketsController extends OrionRelationsController
{
    protected $model = TicketType::class;

    protected $relation = 'purchasedTickets';

    /**
     * @return string[]
     */
    public function includes(): array
    {
        return ['ticketType', 'user', 'event', 'reservedTicket'];
    }

    /**
     * @param  string[]  $requestedRelations
     * @return Relation<PurchasedTicket>
     */
    protected function buildIndexFetchQuery(Request $request, Model $event, array $requestedRelations): Relation
    {
        $relation = parent::buildIndexFetchQuery($request, $event, $requestedRelations);
        /** @var User $user */
        $user = auth()->user();

        // Hide tickets that don't belong to the user if they don't have permission to view all of them
        if (! $user->can('purchasedTickets.viewAny')) {
            $relation->getQuery()->where('user_id', $user->id);
        }

        return $relation;
    }
}
