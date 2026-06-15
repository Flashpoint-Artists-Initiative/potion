<?php

declare(strict_types=1);

namespace App\Filament\Pages\Auth;

use App\Filament\Traits\HasAuthComponents;
use Filament\Auth\Pages\Register as BaseRegister;
use Filament\Schemas\Schema;

class Register extends BaseRegister
{
    use HasAuthComponents;

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                $this->getLegalNameFormComponent(),
                $this->getPreferredNameFormComponent(),
                $this->getEmailFormComponent(),
                $this->getBirthdayFormComponent(),
                $this->getPasswordFormComponent(),
                $this->getPasswordConfirmationFormComponent(),
            ]);
    }
}
