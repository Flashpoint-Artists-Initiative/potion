<?php

declare(strict_types=1);

namespace App\Filament\App\Pages;

use App\Enums\GrantFundingStatusEnum;
use App\Enums\LockdownEnum;
use App\Forms\Components\ArtProjectItemField;
use App\Models\Event;
use App\Models\Grants\ArtProject;
use App\Models\User;
use App\Rules\ArtProjectVotingRule;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Schema;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\HtmlString;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\On;

/**
 * @property Schema $form
 */
class ArtGrants extends Page
{
    protected static ?int $navigationSort = 4;

    protected static BackedEnum|string|null $navigationIcon = 'heroicon-o-paint-brush';

    protected string $view = 'filament.app.pages.art-grants';

    // protected static ?string $navigationLabel = 'Art Grant Voting';s

    /** @var array<mixed> */
    public array $votes;

    public int $maxVotes;

    public bool $hasVoted;

    public bool $votingIsOpen;

    public string $artGrantContent;

    public function __construct()
    {
        if (LockdownEnum::Grants->isLocked()) {
            $this->votingIsOpen = false;
        } else {
            $this->votingIsOpen = Event::getCurrentEvent()->votingIsOpen ?? false;
        }
    }

    public static function getNavigationLabel(): string
    {
        return Event::getCurrentEvent()?->votingIsOpen ? 'Art Grant Voting' : 'Funded Art Projects';
    }

    public static function shouldRegisterNavigation(): bool
    {
        return Event::getCurrentEvent()->votingEnabled ?? false;
    }

    public function getTitle(): string|Htmlable
    {
        $eventName = Event::getCurrentEvent()->name ?? '';
        $openVoting = Event::getCurrentEvent()->votingIsOpen ?? false;

        $suffix = $openVoting ? ' Art Grant Voting' : ' Funded Art Projects';

        return $eventName . $suffix;
    }

    public function form(Schema $schema): Schema
    {
        $projects = once(fn () => ArtProject::query()->currentEvent()->approved()->orderBy('name', 'asc')->get());

        // When voting is closed hide the unfunded projects
        if (! $this->votingIsOpen) {
            $projects = $projects->filter(fn (ArtProject $project) => $project->fundingStatus !== GrantFundingStatusEnum::Unfunded);
        }

        $projectsSchema = $projects->map(function (ArtProject $project) {
            return ArtProjectItemField::make('votes.' . $project->id)
                ->model($project)
                ->rule(new ArtProjectVotingRule) // Whole form validation is handled in the rule for only the last item
                ->hiddenLabel()
                ->default(0)
                ->dehydrateStateUsing(fn ($state) => $state === 0 ? null : $state)
                ->view('forms.components.art-project-item')
                ->disableVoting(fn () => $this->hasVoted || ! $this->votingIsOpen);
        });

        return $schema
            ->components($projectsSchema->toArray());
    }

    #[On('active-event-updated')]
    public function mount(): void
    {
        if (Event::getCurrentEvent()?->votingEnabled == false) {
            redirect(Dashboard::getUrl());
        }

        $this->form->fill();
        $this->maxVotes = Event::getCurrentEvent()->votesPerUser ?? 0;
        $this->hasVoted = Auth::user()?->hasVotedArtProjectsForEvent(Event::getCurrentEventId()) ?? true;

        if (LockdownEnum::Grants->isLocked()) {
            $this->votingIsOpen = false;
        } else {
            $this->votingIsOpen = Event::getCurrentEvent()->votingIsOpen ?? false;
        }

        $this->artGrantContent = Event::getCurrentEvent()?->artGrantVotingContent->formattedContent ?? '';
    }

    public function submitVotes(): void
    {
        $data = $this->form->getState();

        $filteredData = array_filter($data['votes'], function ($vote) {
            return $vote > 0;
        });
        $ids = array_keys($filteredData);

        ArtProject::findMany($ids)->each(function (ArtProject $project) use ($filteredData) {
            /** @var User $user */
            $user = Auth::user();
            $project->vote($user, $filteredData[$project->id]);
        });

        $this->hasVoted = true;
        Notification::make()
            ->title('Your votes have been submitted!')
            ->success()
            ->send();
    }

    protected function onValidationError(ValidationException $exception): void
    {
        Notification::make()
            ->title($exception->getMessage())
            ->danger()
            ->send();
    }

    public function openModal(): Action
    {
        return Action::make('projectDetailsModal')
            ->label('Project Details')
            ->record(fn (array $arguments): ?ArtProject => ($arguments['id'] ?? null) !== null
                ? ArtProject::with('media')->findOrFail((int) $arguments['id'])
                : null)
            ->modalHeading(fn (?ArtProject $record) => $record !== null ? $record->name : 'Project Details')
            ->modalContent(fn (?ArtProject $record) => $record ? $this->generateModalContent($record) : new HtmlString(''))
            ->modalSubmitAction(false)
            ->modalAutofocus(false)
            ->modalCancelActionLabel('Close');
    }

    protected function generateModalContent(ArtProject $project): HtmlString
    {
        return new HtmlString(
            Blade::render(
                '<x-art-project-modal :project=$project></x-art-project-modal>',
                ['project' => $project]
            )
        );
    }
}
