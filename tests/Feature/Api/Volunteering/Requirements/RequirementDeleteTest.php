<?php

declare(strict_types=1);

namespace Tests\Feature\Api\Volunteering\Requirements;

use App\Enums\RolesEnum;
use App\Models\User;
use PHPUnit\Framework\Attributes\Test;
use Tests\ApiRouteTestCase;

class RequirementDeleteTest extends ApiRouteTestCase
{
    public bool $seed = true;

    public string $routeName = 'api.shift-requirements.destroy';

    public array $routeParams = ['shift_requirement' => 1];

    #[Test]
    public function requirement_delete_call_while_not_logged_in_fails(): void
    {
        $response = $this->delete($this->endpoint);

        $response->assertStatus(401);
    }

    #[Test]
    public function requirement_delete_call_without_permission_fails(): void
    {
        $user = User::doesntHave('roles')->firstOrFail();

        $response = $this->actingAs($user)->delete($this->endpoint);

        $response->assertStatus(403);
    }

    #[Test]
    public function requirement_delete_call_as_admin_succeeds(): void
    {
        $user = User::role(RolesEnum::Admin)->firstOrFail();

        $response = $this->actingAs($user)->delete($this->endpoint);

        $response->assertStatus(200);
    }
}
