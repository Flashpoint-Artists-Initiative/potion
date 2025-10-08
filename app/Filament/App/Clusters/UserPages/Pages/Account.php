<?php

declare(strict_types=1);

namespace App\Filament\App\Clusters\UserPages\Pages;

use App\Enums\LockdownEnum;
use App\Filament\App\Clusters\UserPages;
use App\Filament\Traits\HasAuthComponents;
use App\Models\User;
use App\Rules\ValidEmail;
use Filament\Actions\Action;
use Filament\Facades\Filament;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\TextInput;
use Filament\Infolists\Components\Section;
use Filament\Infolists\Components\TextEntry;
use Filament\Infolists\Infolist;
use Filament\Pages\Page;
use Filament\Support\Colors\Color;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\HtmlString;

class Account extends Page
{
    use HasAuthComponents;

    protected static ?string $navigationIcon = 'heroicon-o-user';

    protected static ?string $navigationLabel = 'Account Details';

    protected static ?string $title = 'Account Details';

    protected static string $view = 'filament.app.clusters.user-pages.pages.account';

    protected static ?int $navigationSort = 10;

    protected static ?string $cluster = UserPages::class;

    public function accountInfolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->record(filament()->auth()->user())
            ->schema([
                Section::make('Click to view legal name')
                    ->schema([
                        TextEntry::make('legal_name'),
                    ])
                    ->collapsed()
                    ->columnSpan(1),
                TextEntry::make('preferred_name')
                    ->placeholder('None'),
                TextEntry::make('email'),
                TextEntry::make('birthday')
                    ->date('F jS, Y'),
                TextEntry::make('created_at')
                    ->label('Account Created')
                    ->date('F jS, Y'),
            ])
            ->columns(2);
    }

    protected function getHeaderActions(): array
    {
        $editButton = $this->editModalAction();

        if (LockdownEnum::Tickets->isLocked()) {
            $editButton = Action::make('edit')
                ->modalHeading('Edit Account Details')
                ->modalContent(new HtmlString(
                    'You cannot edit your account details at this time, the site data has been moved offline for the event.'
                ))
                ->modalSubmitAction(false);
        }

        return [
            $editButton,
            Action::make('delete')
                ->requiresConfirmation()
                ->modalDescription('Are you sure you want to delete your account? This action cannot be undone.  Any tickets you have purchased will no longer be available.')
                ->modalHeading('Delete Account')
                ->color(Color::Red)
                ->action(function () {
                    /** @var User */
                    $user = Auth::user();
                    $user->delete();
                    Auth::logout();
                    $this->redirect(Filament::getLoginUrl());
                }),
        ];
    }

    protected function editModalAction(): Action
    {
        return Action::make('edit')
            ->modalHeading('Edit Account Details')
            ->fillForm(function () {
                /** @var User */
                $user = Auth::user();

                return [
                    // 'legal_name' => $user->legal_name,
                    'preferred_name' => $user->preferred_name,
                    'email' => $user->email,
                    // 'birthday' => $user->birthday,
                ];
            })
            ->form([
                Placeholder::make('legal_name')
                    ->label('')
                    ->helperText(new HtmlString('Your legal name and birthday cannot be changed.  If you need to change it, please contact <a class="text-primary-400" href="mailto:' . config('mail.from.address') . '?subject=Legal Name Change">' . config('mail.from.address') . '</a>.')),
                $this->getPreferredNameFormComponent(),
                TextInput::make('email')
                    ->rule(new ValidEmail)
                    ->required()
                    ->maxLength(255)
                    ->unique(ignoreRecord: true)
                    ->helperText('Changing your email address will require re-verification.'),
                $this->getPasswordFormComponent(false),
                $this->getPasswordConfirmationFormComponent(false),
            ])
            ->action(function (array $data) {
                /** @var User */
                $user = filament()->auth()->user();
                // $user->legal_name = $data['legal_name'];
                $user->preferred_name = $data['preferred_name'];
                $user->email = $data['email'];
                $user->password = $data['password'] ?? $user->password;
                $user->save();
            });
    }
}
