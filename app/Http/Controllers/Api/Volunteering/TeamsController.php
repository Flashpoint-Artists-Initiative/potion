<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\Volunteering;

use App\Http\Controllers\OrionRelationsController;
use App\Http\Requests\Volunteering\TeamRequest;
use App\Models\Event;
use App\Models\User;
use App\Models\Volunteering\Team;
use App\Policies\TeamPolicy;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Http\Request;

class TeamsController extends OrionRelationsController
{
    protected $model = Event::class;

    protected $relation = 'teams';

    protected $request = TeamRequest::class;

    protected $policy = TeamPolicy::class;

    public function __construct()
    {
        $this->middleware(['lockdown:volunteer'])->except(['index', 'show', 'search']);

        parent::__construct();
    }

    /**
     * @param  string[]  $requestedRelations
     * @return Relation<Team>
     */
    protected function buildIndexFetchQuery(Request $request, Model $event, array $requestedRelations): Relation
    {
        $relation = parent::buildIndexFetchQuery($request, $event, $requestedRelations);
        /** @var ?User $user */
        $user = auth()->user();

        // Hide non-active teams for users without specific permission to view them
        if (! $user?->can('teams.viewPending')) {
            $relation->getQuery()->where('active', true);
        }

        // Hide soft-deleted teams for users without specific permission to view them
        if (! $user?->can('teams.viewDeleted')) {
            // @phpstan-ignore-next-line
            $relation->getQuery()->withoutTrashed();
        }

        return $relation;
    }

    /**
     * @param  string[]  $requestedRelations
     * @return Relation<Team>
     */
    protected function buildShowFetchQuery(Request $request, Model $event, array $requestedRelations): Relation
    {
        $relation = parent::buildShowFetchQuery($request, $event, $requestedRelations);
        /** @var ?User $user */
        $user = auth()->user();

        // Hide soft-deleted teams for users without specific permission to view them
        if (! $user?->can('teams.viewDeleted')) {
            // @phpstan-ignore-next-line
            $relation->getQuery()->withoutTrashed();
        }

        return $relation;
    }
}
