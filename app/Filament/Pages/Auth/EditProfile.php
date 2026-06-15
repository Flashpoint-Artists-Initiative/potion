<?php

declare(strict_types=1);

namespace App\Filament\Pages\Auth;

use App\Filament\Traits\HasAuthComponents;
use Filament\Auth\Pages\EditProfile as BaseEditProfile;
use Filament\Schemas\Schema;

class EditProfile extends BaseEditProfile
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
