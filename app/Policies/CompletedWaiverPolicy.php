<?php

declare(strict_types=1);

namespace App\Policies;

use App\Enums\LockdownEnum;

class CompletedWaiverPolicy extends AbstractModelPolicy
{
    protected string $prefix = 'completedWaivers';

    protected ?LockdownEnum $lockdownKey = LockdownEnum::Tickets;
}
