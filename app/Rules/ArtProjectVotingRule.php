<?php

declare(strict_types=1);

namespace App\Rules;

use App\Models\Event;
use App\Models\Grants\ArtProject;
use Closure;
use Illuminate\Contracts\Validation\DataAwareRule;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Support\Facades\Auth;
use Illuminate\Translation\PotentiallyTranslatedString;

class ArtProjectVotingRule implements DataAwareRule, ValidationRule
{
    /** @var array<string, mixed> */
    protected array $data = [];

    /**
     * Run the validation rule.
     *
     * @param  Closure(string, ?string=): PotentiallyTranslatedString  $fail
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        // $attribute format is `votes.{id}`
        $id = collect(explode('.', $attribute))->last();
        $lastId = collect(array_keys($this->data['votes'] ?? []))->last();

        // Only check these on the last item so we don't show multiple errors
        if ($id == $lastId) {
            $totalVotes = array_sum($this->data['votes'] ?? []);
            $maxVotes = Event::getCurrentEvent()->votesPerUser ?? 0;

            if ($totalVotes > $maxVotes) {
                $fail('You cannot submit more than ' . $maxVotes . ' votes.');

                return;
            }

            if ($totalVotes < $maxVotes) {
                $fail('Use all your votes before submitting to support your favorite projects!');

                return;
            }
        }

        // Ignore 0 votes for this project
        if ($value === 0) {
            return;
        }

        $project = ArtProject::find($id);
        if (! $project) {
            $fail('Invalid project ID');

            return;
        }

        try {
            $project->checkVotingStatus(Auth::user());
        } catch (\Exception $e) {
            $fail($e->getMessage());

            return;
        }

    }

    /** @param array<string, mixed> $data */
    public function setData(array $data): static
    {
        $this->data = $data;

        return $this;
    }
}
