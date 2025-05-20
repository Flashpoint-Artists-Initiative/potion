<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\Volunteering;

use App\Http\Controllers\OrionRelationsController;
use App\Http\Requests\Volunteering\ShiftRequest;
use App\Models\Volunteering\ShiftType;
use App\Policies\ShiftPolicy;
use App\Policies\ShiftTypePolicy;

class ShiftsController extends OrionRelationsController
{
    protected $model = ShiftType::class;

    protected $relation = 'shifts';

    protected $request = ShiftRequest::class;

    protected $policy = ShiftPolicy::class;

    /** @var class-string */
    protected $parentPolicy = ShiftTypePolicy::class;

    public function __construct()
    {
        $this->middleware(['lockdown:volunteer'])->except(['index', 'show', 'search']);

        parent::__construct();
    }
}
