<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\Volunteering;

use App\Http\Controllers\OrionRelationsController;
use App\Http\Requests\Volunteering\ShiftRequest;
use App\Models\Volunteering\ShiftType;
use App\Policies\Volunteering\ShiftPolicy;
use App\Policies\Volunteering\ShiftTypePolicy;

class ShiftsController extends OrionRelationsController
{
    protected $model = ShiftType::class;

    protected $relation = 'shifts';

    protected $request = ShiftRequest::class;

    protected $policy = ShiftPolicy::class;

    protected $parentPolicy = ShiftTypePolicy::class;
}
