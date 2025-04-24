<?php

declare(strict_types=1);

namespace App\Filament\App\Pages;

use App\Forms\Components\ArtProjectItemField;
use App\Models\Event;
use App\Models\Grants\ArtProject;
use App\Models\User;
use App\Rules\ArtProjectVotingRule;
use Filament\Actions\Action;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\HtmlString;
use Illuminate\Validation\ValidationException;

/**
 * @property Form $form
 */
class ArtGrants extends Page
{
    protected static ?int $navigationSort = 4;

    protected static ?string $navigationIcon = 'heroicon-o-paint-brush';

    protected static string $view = 'filament.app.pages.art-grants';

    protected static ?string $navigationLabel = 'Art Grant Voting';

    /** @var array<mixed> */
    public array $votes;

    public int $maxVotes;

    public bool $hasVoted;

    public static function shouldRegisterNavigation(): bool
    {
        return Event::getCurrentEvent()->votingEnabled ?? false;
    }

    public function getTitle(): string|Htmlable
    {
        $eventName = Event::getCurrentEvent()->name ?? '';

        return $eventName . ' Art Grant Voting';
    }

    public function form(Form $form): Form
    {
        $projects = ArtProject::query()->currentEvent()->approved()->orderBy('name', 'desc')->get();

        $projectsSchema = $projects->map(function (ArtProject $project) {
            return ArtProjectItemField::make('votes.' . $project->id)
                ->model($project)
                ->rule(new ArtProjectVotingRule) // Whole form validation is handled in the rule for only the last item
                ->hiddenLabel()
                ->default(0)
                ->dehydrateStateUsing(fn ($state) => $state === 0 ? null : $state)
                ->view('forms.components.art-project-item')
                ->disableVoting(fn () => $this->hasVoted);
        });

        return $form
            ->schema($projectsSchema->toArray());
    }

    public function mount(): void
    {
        if (Event::getCurrentEvent()?->votingEnabled == false) {
            abort(404);
        }
        $this->form->fill();
        $this->maxVotes = Event::getCurrentEvent()->votesPerUser ?? 0;
        $this->hasVoted = Auth::user()?->hasVotedArtProjectsForEvent(Event::getCurrentEventId()) ?? true;
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
            ->modalHeading(fn (array $arguments) => ArtProject::findOrFail((int) $arguments['id'])->name)
            ->modalContent(fn (array $arguments) => $this->generateModalContent($arguments))
            ->modalSubmitAction(false)
            ->modalAutofocus(false)
            ->modalCancelActionLabel('Close');
    }

    /**
     * @param  array<mixed>  $arguments
     */
    protected function generateModalContent(array $arguments): HtmlString
    {
        $project = ArtProject::find($arguments['id']);

        return new HtmlString(
            Blade::render(
                '<x-art-project-modal :project=$project>Hello</x-art-project-modal>',
                ['project' => $project]
            )
        );
    }
}
