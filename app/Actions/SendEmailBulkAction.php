<?php
declare(strict_types=1);

namespace App\Actions;

use App\Models\User;
use App\Notifications\UserEmailNotification;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\TextInput;
use Filament\Tables\Actions\BulkAction;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;

class SendEmailBulkAction extends BulkAction
{
    public static function getDefaultName(): ?string
    {
        return 'send-email';
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this->label(__('Send Email'));

        $this->modalHeading(fn (): string => __('Send Email to Selected Users'));

        $this->modalSubmitActionLabel(__('Send'));

        $this->successNotificationTitle(__('Email sent successfully!'));

        // $this->color('primary');

        $this->icon('heroicon-o-envelope');

        // $this->requiresConfirmation();

        $this->form($this->getFormSchema());

        $this->action(function (array $data, Collection $records): void {
            /** @var Collection<int, User> $records */
            $records->each(fn (User $user) => $user->notify(new UserEmailNotification(
                subject: $data['subject'],
                content: $data['content']
            )));

            $this->success();
        });

        $this->deselectRecordsAfterCompletion();

        $this->modalSubmitActionLabel(fn(Collection $records): string => __('Send Email to :count Users', ['count' => $records->count()]));
    }

    /**
     * @return array<\Filament\Forms\Components\Component> The form schema for the action.
     */
    protected function getFormSchema(): array
    {
        return [
            TextInput::make('subject')
                ->required(),
            RichEditor::make('content')
                ->required(),
        ];
    }
}
