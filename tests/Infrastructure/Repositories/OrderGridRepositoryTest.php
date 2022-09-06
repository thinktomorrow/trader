<?php
declare(strict_types=1);

namespace Tests\Infrastructure\Repositories;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Tests\Infrastructure\TestCase;
use Thinktomorrow\Trader\Application\Order\Grid\OrderGridItem;
use Thinktomorrow\Trader\Domain\Model\Order\State\OrderState;
use Thinktomorrow\Trader\Infrastructure\Laravel\Models\DefaultOrderGridItem;
use Thinktomorrow\Trader\Infrastructure\Laravel\Repositories\MysqlOrderGridRepository;
use Thinktomorrow\Trader\Infrastructure\Laravel\Repositories\MysqlOrderRepository;
use Thinktomorrow\Trader\Infrastructure\Test\TestContainer;
use Thinktomorrow\Trader\Infrastructure\Test\TestTraderConfig;

class OrderGridRepositoryTest extends TestCase
{
    use RefreshDatabase;

    private \Thinktomorrow\Trader\Domain\Model\Order\Order $order;

    protected function setUp(): void
    {
        parent::setUp();

        $this->order = $this->createDefaultOrder();
        (new TestContainer())->get(MysqlOrderRepository::class)->save($this->order);
    }

    /** @test */
    public function it_fetches_grid_orders()
    {
        $gridItems = $this->getMysqlGridRepository()->getResults();

        $this->assertCount(1, $gridItems);
    }

    /** @test */
    public function it_can_return_ids()
    {
        $gridItems = $this->getMysqlGridRepository()->getOrderIds();

        $this->assertCount(1, $gridItems);
    }

    /** @test */
    public function it_can_filter_by_order_reference()
    {
        $gridItems = $this->getMysqlGridRepository()->filterByOrderReference('xx')->getResults();

        $this->assertCount(1, $gridItems);
    }

    /** @test */
    public function it_can_filter_by_shopper_email()
    {
        $gridItems = $this->getMysqlGridRepository()->filterByShopperEmail('ben@think')->getResults();

        $this->assertCount(1, $gridItems);
    }

    /** @test */
    public function it_can_filter_by_customer_id()
    {
        $gridItems = $this->getMysqlGridRepository()->filterByCustomerId('ccc-123')->getResults();
        $this->assertCount(1, $gridItems);

        $this->assertCount(0, $this->getMysqlGridRepository()->filterByCustomerId('ccc')->getResults());
    }

    /** @test */
    public function it_can_filter_by_order_states()
    {
        $this->assertCount(1, $this->getMysqlGridRepository()->filterByStates([OrderState::cart_revived->value])->getResults());
        $this->assertCount(0, $this->getMysqlGridRepository()->filterByStates([OrderState::cancelled->value])->getResults());
    }

    /** @test */
    public function it_can_filter_by_confirmed_at()
    {
        $this->assertCount(0, $this->getMysqlGridRepository()->filterByConfirmedAt(now()->subDay()->toDateString(), now()->addDay()->toDateString())->getResults());

        // Force timestamp
        $this->updateRow('xxx', ['confirmed_at' => now()->toDateTimeString()]);

        $this->assertCount(1, $this->getMysqlGridRepository()->filterByConfirmedAt(now()->subDay()->toDateTimeString(), now()->addDay()->toDateTimeString())->getResults());
        $this->assertCount(1, $this->getMysqlGridRepository()->filterByConfirmedAt(now()->subDay()->toDateString(), now()->addDay()->toDateString())->getResults());
        $this->assertCount(0, $this->getMysqlGridRepository()->filterByConfirmedAt(now()->addDay()->toDateTimeString())->getResults());
        $this->assertCount(0, $this->getMysqlGridRepository()->filterByConfirmedAt(null, now()->subDay()->toDateTimeString())->getResults());
    }

    /** @test */
    public function it_can_filter_by_delivered_at()
    {
        $this->assertCount(0, $this->getMysqlGridRepository()->filterByDeliveredAt(now()->subDay()->toDateString(), now()->addDay()->toDateString())->getResults());

        // Force timestamp
        $this->updateRow('xxx', ['delivered_at' => now()->toDateTimeString()]);

        $this->assertCount(1, $this->getMysqlGridRepository()->filterByDeliveredAt(now()->subDay()->toDateTimeString(), now()->addDay()->toDateTimeString())->getResults());
        $this->assertCount(1, $this->getMysqlGridRepository()->filterByDeliveredAt(now()->subDay()->toDateString(), now()->addDay()->toDateString())->getResults());
        $this->assertCount(0, $this->getMysqlGridRepository()->filterByDeliveredAt(now()->addDay()->toDateTimeString())->getResults());
        $this->assertCount(0, $this->getMysqlGridRepository()->filterByDeliveredAt(null, now()->subDay()->toDateTimeString())->getResults());
    }

    /** @test */
    public function it_can_sort_by_confirmed_at()
    {
        $this->runTestSortingByDate('confirmed_at', 'sortByConfirmedAt', 'getConfirmedAt');
    }

    /** @test */
    public function it_can_sort_descending_by_confirmed_at()
    {
        $this->runTestSortingByDate('confirmed_at', 'sortByConfirmedAtDesc', 'getConfirmedAt');
    }

    /** @test */
    public function it_can_sort_by_delivered_at()
    {
        $this->runTestSortingByDate('delivered_at', 'sortByDeliveredAt', 'getDeliveredAt');
    }

    /** @test */
    public function it_can_sort_descending_by_delivered_at()
    {
        $this->runTestSortingByDate('delivered_at', 'sortByDeliveredAtDesc', 'getDeliveredAt');
    }

    private function runTestSortingByDate(string $column, string $sortingMethod, string $modelMethod)
    {
        $order = $this->createOrder(['order_id' => 'yyy', 'order_ref' => 'yy-ref', 'invoice_ref' => 'yy-invoice-ref'], [], [], [], [], null, null, $this->createOrderShopper(['shopper_id' => 'sss']));
        (new TestContainer())->get(MysqlOrderRepository::class)->save($order);

        $this->updateRow('xxx', [$column => now()->addHour()->toDateTimeString()]);
        $this->updateRow('yyy', [$column => now()->toDateTimeString()]);

        $gridItems = $this->getMysqlGridRepository()->{$sortingMethod}()->getResults();

        $this->assertCount(2, $gridItems);

        $previousDatetime = null;
        foreach ($gridItems as $gridItem) {
            $dateTime = $gridItem->{$modelMethod}();

            if ($previousDatetime) {
                if (Str::endsWith($sortingMethod, 'Desc')) {
                    $this->assertLessThan($previousDatetime, $dateTime);
                } else {
                    $this->assertGreaterThan($previousDatetime, $dateTime);
                }
            }

            $previousDatetime = $dateTime;
        }
    }

    private function getMysqlGridRepository()
    {
        (new TestContainer())->add(OrderGridItem::class, DefaultOrderGridItem::class);

        return new MysqlOrderGridRepository(
            new TestContainer(),
            new TestTraderConfig(),
        );
    }

    private function updateRow(string $orderId, array $values)
    {
        DB::table('trader_orders')->where('order_id', $orderId)->update($values);
    }
}
