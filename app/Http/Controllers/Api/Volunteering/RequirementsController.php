<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\Volunteering;

use App\Http\Controllers\OrionController;
use App\Http\Requests\Volunteering\RequirementRequest;
use App\Models\User;
use App\Models\Volunteering\Requirement;
use App\Policies\ShiftRequirementPolicy;
use Illuminate\Database\Eloquent\Builder;
use Orion\Http\Requests\Request;

class RequirementsController extends OrionController
{
    protected $model = Requirement::class;

    protected $policy = ShiftRequirementPolicy::class;

    protected $request = RequirementRequest::class;

    public function __construct()
    {
        $this->middleware(['lockdown:volunteer'])->except(['index', 'show', 'search']);

        parent::__construct();
    }

    /**
     * @param  string[]  $requestedRelations
     * @return Builder<Requirement>
     */
    protected function buildIndexFetchQuery(Request $request, array $requestedRelations): Builder
    {
        $query = parent::buildIndexFetchQuery($request, $requestedRelations);
        /** @var ?User $user */
        $user = auth()->user();

        // Hide soft-deleted teams for users without specific permission to view them
        if (! $user?->can('requirements.viewDeleted')) {
            // @phpstan-ignore-next-line
            $query->withoutTrashed();
        }

        return $query;
    }

    /**
     * @param  string[]  $requestedRelations
     * @return Builder<Requirement>
     */
    protected function buildShowFetchQuery(Request $request, array $requestedRelations): Builder
    {
        $query = parent::buildShowFetchQuery($request, $requestedRelations);
        /** @var ?User $user */
        $user = auth()->user();

        // Hide soft-deleted teams for users without specific permission to view them
        if (! $user?->can('requirements.viewDeleted')) {
            // @phpstan-ignore-next-line
            $query->withoutTrashed();
        }

        return $query;
    }
}
