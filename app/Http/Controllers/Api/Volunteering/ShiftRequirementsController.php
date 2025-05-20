<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\Volunteering;

use App\Http\Controllers\OrionRelationsController;
use App\Models\Volunteering\ShiftType;
use App\Policies\ShiftRequirementPolicy;
use App\Policies\ShiftTypePolicy;

class ShiftRequirementsController extends OrionRelationsController
{
    protected $model = ShiftType::class;

    protected $relation = 'requirements';

    protected $policy = ShiftRequirementPolicy::class;

    /** @var class-string */
    protected $parentPolicy = ShiftTypePolicy::class;

    public function __construct()
    {
        $this->middleware(['lockdown:volunteer'])->except(['index', 'show', 'search']);

        parent::__construct();
    }
}
