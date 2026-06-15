<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\Events;

use App\Http\Controllers\OrionRelationsController;
use App\Models\Event;
use App\Models\Ticketing\TicketType;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\Relation;
use Orion\Http\Requests\Request;

class TicketTypesController extends OrionRelationsController
{
    protected $model = Event::class;

    protected $relation = 'ticketTypes';

    public function __construct()
    {
        $this->middleware(['auth', 'lockdown:ticket'])->except(['index', 'show', 'search']);

        parent::__construct();
    }

    /**
     * @return string[]
     */
    public function includes(): array
    {
        return ['event', 'purchasedTickets', 'reservedTickets', 'cartItems'];
    }

    /**
     * @return string[]
     */
    public function filterableBy(): array
    {
        return ['id'];
    }

    /**
     * @param  string[]  $requestedRelations
     * @return Relation<TicketType>
     */
    protected function buildIndexFetchQuery(Request $request, Model $event, array $requestedRelations): Relation
    {
        $relation = parent::buildIndexFetchQuery($request, $event, $requestedRelations);
        /** @var ?User $user */
        $user = auth()->user();

        // Hide non-active ticketTypes for users without specific permission to view them
        if (! $user?->can('ticketTypes.viewPending')) {
            $relation->getQuery()->where('active', true);
        }

        // Hide soft-deleted ticketTypes for users without specific permission to view them
        if (! $user?->can('ticketTypes.viewDeleted')) {
            // @phpstan-ignore-next-line
            $relation->getQuery()->withoutTrashed();
        }

        return $relation;
    }

    /**
     * @param  string[]  $requestedRelations
     * @return Relation<TicketType>
     */
    protected function buildShowFetchQuery(Request $request, Model $event, array $requestedRelations): Relation
    {
        $relation = parent::buildShowFetchQuery($request, $event, $requestedRelations);
        /** @var ?User $user */
        $user = auth()->user();

        // Hide soft-deleted ticketTypes for users without specific permission to view them
        if (! $user?->can('ticketTypes.viewDeleted')) {
            // @phpstan-ignore-next-line
            $relation->getQuery()->withoutTrashed();
        }

        return $relation;
    }
}
