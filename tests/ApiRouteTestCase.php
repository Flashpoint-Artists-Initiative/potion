<?php

declare(strict_types=1);

namespace Tests;

use App\Models\User;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPOpenSourceSaver\JWTAuth\JWTGuard;

abstract class ApiRouteTestCase extends TestCase
{
    use LazilyRefreshDatabase;
    // use RefreshDatabase;

    public string $routeName;

    /** @var array<string, string|int|bool> */
    public array $routeParams = [];

    public string $endpoint;

    /** @var string[] */
    public array $connectionsToTransact = ['testing'];

    protected function setUp(): void
    {
        parent::setUp();

        $this->buildEndpoint();
    }

    /**
     * @param  array<string, string|int|bool>  $params
     */
    public function buildEndpoint(?string $name = null, ?array $params = null): void
    {
        if (empty($name)) {
            $name = $this->routeName;
        }

        if (empty($params)) {
            $params = $this->routeParams;
        }

        if (! empty($name)) {
            $this->endpoint = route($name, $params, false);
        }
    }

    /**
     * @param  array<string, string|int|bool>  $params
     */
    public function addEndpointParams(array $params): void
    {
        $this->routeParams = array_merge($this->routeParams, $params);
        $this->buildEndpoint();
    }

    /**
     * @param  User  $user
     */
    public function actingAs(Authenticatable $user, $guard = null)
    {
        /** @var JWTGuard */
        $auth = auth('api');
        $token = $auth->login($user);

        return parent::actingAs($user, $guard)->withToken($token);
    }
}
