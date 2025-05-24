<?php

declare(strict_types=1);

namespace Tests\Unit\Models\Grants;

use App\Enums\ArtProjectStatusEnum;
use App\Enums\GrantFundingStatusEnum;
use App\Models\Event;
use App\Models\Grants\ArtProject;
use App\Models\User;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class ArtProjectTest extends TestCase
{
    /**
     * Test that an ArtProject can be created.
     */
    #[Test]
    public function art_project_can_be_created(): void
    {
        $user = User::factory()->create();
        $event = Event::factory()->create();
        $artProject = ArtProject::create([
            'name' => 'Test Project',
            'user_id' => $user->id,
            'event_id' => $event->id,
            'description' => 'Test Description',
            'budget_link' => 'http://example.com/budget',
            'min_funding' => 1000,
            'max_funding' => 5000,
            'project_status' => 'pending-review',
        ]);

        $this->assertDatabaseHas('art_projects', [
            'id' => $artProject->id,
            'name' => 'Test Project',
            'user_id' => $user->id,
            'event_id' => $event->id,
            'description' => 'Test Description',
            'budget_link' => 'http://example.com/budget',
            'min_funding' => 1000,
            'max_funding' => 5000,
            'project_status' => 'pending-review',
        ]);
    }

    /**
     * Test the relationship between ArtProject and Event.
     */
    #[Test]
    public function art_project_belongs_to_event(): void
    {
        $event = Event::factory()->create();
        /** @var ArtProject $artProject */
        $artProject = ArtProject::factory()->create(['event_id' => $event->id]);

        $this->assertInstanceOf(Event::class, $artProject->event);
        $this->assertEquals($event->id, $artProject->event->id);
    }

    /**
     * Test the relationship between ArtProject and User.
     */
    #[Test]
    public function art_project_belongs_to_user(): void
    {
        $user = User::factory()->create();
        /** @var ArtProject $artProject */
        $artProject = ArtProject::factory()->create(['user_id' => $user->id]);

        $this->assertInstanceOf(User::class, $artProject->user);
        $this->assertEquals($user->id, $artProject->user->id);
    }

    /**
     * Test the relationship between ArtProject and votes.
     */
    #[Test]
    public function art_project_belongs_to_many_votes(): void
    {
        /** @var ArtProject $artProject */
        $artProject = ArtProject::factory()->create();
        $user = User::factory()->create();
        $artProject->votes()->attach($user->id, ['votes' => 1]);

        $this->assertTrue($artProject->votes->contains($user));
    }

    /**
     * Test the totalFunding attribute.
     */
    #[Test]
    public function funded_total_attribute(): void
    {
        $event = $this->createEventWithVoting();

        /** @var ArtProject $artProject */
        $artProject = ArtProject::factory()->create([
            'project_status' => ArtProjectStatusEnum::Approved,
            'event_id' => $event->id]
        );
        $user = User::factory()->create();
        $artProject->votes()->attach($user->id, ['votes' => 2]);

        $this->assertEquals(20, $artProject->totalFunding);
    }

    /**
     * Test the fundingStatus attribute.
     */
    #[Test]
    public function funding_status_attribute(): void
    {
        $event = $this->createEventWithVoting();

        /** @var ArtProject $artProject */
        $artProject = ArtProject::factory()->create([
            'project_status' => ArtProjectStatusEnum::Approved,
            'event_id' => $event->id,
            'min_funding' => 10,
            'max_funding' => 20,
        ]);
        $user = User::factory()->create();
        $artProject->votes()->attach($user->id, ['votes' => 1]);

        $this->assertEquals(GrantFundingStatusEnum::MinReached, $artProject->fundingStatus);
    }

    /**
     * Test the vote method.
     */
    #[Test]
    public function vote_method(): void
    {
        $event = $this->createEventWithVoting();

        /** @var ArtProject $artProject */
        $artProject = ArtProject::factory()->create([
            'project_status' => ArtProjectStatusEnum::Approved->value,
            'event_id' => $event->id]
        );
        $user = User::factory()->create();

        $artProject->vote($user, 10);
        $artProject->refresh();

        $this->assertTrue($artProject->votes->contains($user));
        $this->assertEquals(100, $artProject->totalFunding);
    }

    /**
     * Test the vote method when voting is closed.
     */
    #[Test]
    public function vote_method_when_voting_is_closed(): void
    {
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Grant voting is closed for this event');

        $event = Event::factory()->create([
            'settings' => [
                'art' => [
                    'dollars_per_vote' => 10,
                    'voting_enabled' => false,
                ],
            ],
        ]);

        /** @var ArtProject $artProject */
        $artProject = ArtProject::factory()->create([
            'project_status' => ArtProjectStatusEnum::Approved->value,
            'event_id' => $event->id]
        );
        $user = User::factory()->create();

        $artProject->vote($user, 1);
    }

    /**
     * Test the vote method when project status is not approved.
     */
    #[Test]
    public function vote_method_when_status_is_not_approved(): void
    {
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Only approved projects can be voted on');

        $event = $this->createEventWithVoting();

        /** @var ArtProject $artProject */
        $artProject = ArtProject::factory()->create([
            'project_status' => ArtProjectStatusEnum::PendingReview->value,
            'event_id' => $event->id]
        );
        $user = User::factory()->create();
        $artProject->votes()->attach($user->id, ['votes' => 1]);

        $artProject->vote($user, 1);
    }

    /**
     * Test the vote method when user has already voted.
     */
    #[Test]
    public function vote_method_when_user_has_already_voted(): void
    {
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('User has already voted for this project');

        $event = $this->createEventWithVoting();

        /** @var ArtProject $artProject */
        $artProject = ArtProject::factory()->create([
            'project_status' => ArtProjectStatusEnum::Approved->value,
            'event_id' => $event->id]
        );
        $user = User::factory()->create();
        $artProject->votes()->attach($user->id, ['votes' => 1]);

        $artProject->vote($user, 1);
    }

    /**
     * Test the vote method when project has reached maximum funding.
     */
    #[Test]
    public function vote_method_when_project_has_reached_maximum_funding(): void
    {
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Project has already reached maximum funding');

        $event = $this->createEventWithVoting();

        /** @var ArtProject $artProject */
        $artProject = ArtProject::factory()->create([
            'project_status' => ArtProjectStatusEnum::Approved->value,
            'event_id' => $event->id,
            'min_funding' => 50,
            'max_funding' => 100,
        ]);
        $user = User::factory()->create();
        $artProject->vote($user, 10);
        $artProject->refresh();

        $this->assertEquals(GrantFundingStatusEnum::MaxReached, $artProject->fundingStatus);

        $secondUser = User::factory()->create();
        $artProject->vote($secondUser, 1);
    }

    protected function createEventWithVoting(): Event
    {
        return Event::factory()->create([
            'settings' => [
                'art' => [
                    'dollars_per_vote' => 10,
                    'voting_enabled' => true,
                ],
            ],
        ]);
    }
}
