<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\Volunteering;

use App\Http\Controllers\OrionRelationsController;
use App\Http\Requests\Volunteering\ShiftTypeRequest;
use App\Models\Volunteering\Team;
use App\Policies\Volunteering\ShiftTypePolicy;
use App\Policies\Volunteering\TeamPolicy;

class ShiftTypesController extends OrionRelationsController
{
    protected $model = Team::class;

    protected $relation = 'shiftTypes';

    protected $request = ShiftTypeRequest::class;

    protected $policy = ShiftTypePolicy::class;

    protected $parentPolicy = TeamPolicy::class;
}
