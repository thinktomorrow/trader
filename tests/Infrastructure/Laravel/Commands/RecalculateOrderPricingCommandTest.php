<?php

declare(strict_types=1);

namespace Tests\Infrastructure\Laravel\Commands;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Thinktomorrow\Trader\Domain\Model\Order\Order;
use Thinktomorrow\Trader\Domain\Model\Order\OrderId;
use Thinktomorrow\Trader\Domain\Model\Order\OrderReference;
use Thinktomorrow\Trader\Domain\Model\Order\OrderRepository;
use Thinktomorrow\Trader\Domain\Model\Order\State\DefaultOrderState;
use Thinktomorrow\Trader\Infrastructure\Laravel\TraderServiceProvider;

final class RecalculateOrderPricingCommandTest extends \Orchestra\Testbench\TestCase
{
    protected function getEnvironmentSetUp($app): void
    {
        $app['config']->set('database.default', 'testing');
    }

    public function test_it_recalculates_an_order_without_persisting_during_a_dry_run(): void
    {
        $order = Order::create(
            OrderId::fromString('order-pricing-command'),
            OrderReference::fromString('ORDER-PRICING-COMMAND'),
            DefaultOrderState::cart_pending,
        );
        $repository = $this->createMock(OrderRepository::class);
        $repository->method('find')->with($order->orderId)->willReturn($order);
        $repository->expects($this->never())->method('save');
        $this->app->instance(OrderRepository::class, $repository);

        $this->artisan('trader:recalculate-order-pricing', [
            'orderId' => $order->orderId->get(),
            '--dry-run' => true,
        ])
            ->expectsOutputToContain('Dry run completed')
            ->assertSuccessful();

        $this->assertFalse($order->hasPricingSnapshot());
    }

    public function test_it_persists_a_recalculated_order(): void
    {
        Schema::create('trader_orders', function (Blueprint $table): void {
            $table->string('order_id')->primary();
        });

        $order = Order::create(
            OrderId::fromString('order-pricing-command'),
            OrderReference::fromString('ORDER-PRICING-COMMAND'),
            DefaultOrderState::cart_pending,
        );
        DB::table('trader_orders')->insert(['order_id' => $order->orderId->get()]);
        $repository = $this->createMock(OrderRepository::class);
        $repository->method('find')->with($order->orderId)->willReturn($order);
        $repository->expects($this->once())->method('save')->with($order);
        $this->app->instance(OrderRepository::class, $repository);

        $this->artisan('trader:recalculate-order-pricing', [
            'orderId' => $order->orderId->get(),
        ])
            ->expectsOutputToContain('Pricing snapshot recalculated and persisted.')
            ->assertSuccessful();

        $this->assertTrue($order->hasPricingSnapshot());
    }

    protected function getPackageProviders($app): array
    {
        return [TraderServiceProvider::class];
    }
}
