<?php

declare(strict_types=1);

namespace Tests\Feature\Api\Admin\Metrics;

use App\Enums\RolesEnum;
use App\Models\Ticketing\Order;
use App\Models\User;
use App\Services\StripeService;
use Database\Seeders\AccurateOrderSeeder;
use Mockery\Expectation;
use Mockery\MockInterface;
use PHPUnit\Framework\Attributes\Test;
use Stripe\TaxRate;
use Tests\ApiRouteTestCase;

class MetricsSalesDataTest extends ApiRouteTestCase
{
    public bool $seed = true;

    public string $routeName = 'api.admin.metrics.salesData';

    public array $routeParams = ['event_id' => 1];

    protected function setUp(): void
    {
        parent::setUp();

        $this->partialMock(StripeService::class, function (MockInterface $mock) {
            $taxRate = new TaxRate;
            $taxRate->description = 'Sales Tax';
            $taxRate->percentage = 7;

            $taxRateTwo = new TaxRate;
            $taxRateTwo->description = 'Stripe Fee';
            $taxRateTwo->percentage = 2.9;

            /** @var Expectation $receive */
            $receive = $mock->shouldReceive('getTaxRate');
            $receive->andReturn($taxRate, $taxRateTwo);
        });
    }

    #[Test]
    public function metrics_sales_data_call_not_logged_in_returns_error(): void
    {
        $response = $this->get($this->endpoint);

        $response->assertStatus(401);
    }

    #[Test]
    public function metrics_sales_data_call_without_permission_returns_error(): void
    {
        $user = User::doesntHave('roles')->firstOrFail();

        $this->assertFalse($user->can('metrics.salesData'));

        $response = $this->actingAs($user)->get($this->endpoint);

        $response->assertStatus(403);
    }

    #[Test]
    public function metrics_sales_data_call_with_permission_returns_success(): void
    {
        $this->seed(AccurateOrderSeeder::class);
        $order = Order::oldest()->firstOrFail();  // The oldest order (by created_at) will have the correct event_id

        $this->addEndpointParams(['event_id' => $order->event_id]);

        $count = Order::where('event_id', $order->event_id)->count();
        $user = User::role(RolesEnum::Admin)->firstOrFail();

        $this->assertTrue($user->can('metrics.salesData'));

        $response = $this->actingAs($user)->get($this->endpoint);

        $response->assertStatus(200)->assertJsonPath('data.totals.orders_count', $count);
    }
}
