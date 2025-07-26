<?php

declare(strict_types=1);

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

enum GrantFundingStatusEnum implements HasColor, HasLabel
{
    use Concerns\EnumToArray;

    case Unfunded;
    case MinReached;
    case MaxReached;

    public function getLabel(): string
    {
        return match ($this) {
            self::Unfunded => 'Minimum Funding Not Reached',
            self::MinReached => 'Minimum Funding Reached',
            self::MaxReached => 'Maximum Funding Reached',
        };
    }

    public function getColor(): string
    {
        return match ($this) {
            self::Unfunded => 'danger',
            self::MinReached => 'success',
            self::MaxReached => 'success',
        };
    }

    public function getAmountField(): string
    {
        return match ($this) {
            self::Unfunded => 'min_funding',
            self::MinReached => 'min_funding',
            self::MaxReached => 'max_funding',
        };
    }
}
