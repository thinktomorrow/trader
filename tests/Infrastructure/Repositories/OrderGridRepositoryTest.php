<?php
declare(strict_types=1);

namespace Tests\Infrastructure\Repositories;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Tests\Infrastructure\TestCase;
use Thinktomorrow\Trader\Domain\Model\Order\State\DefaultOrderState;

class OrderGridRepositoryTest extends TestCase
{
    private \Thinktomorrow\Trader\Domain\Model\Order\Order $order;

    protected function setUp(): void
    {
        parent::setUp();

        // Order grid repository is only in mysql available, not in memory.
        $this->order = $this->orderContext->createDefaultOrder();
    }

    public function test_it_fetches_grid_orders()
    {
        $results = $this->orderContext->repos()->orderGridRepository()->getResults();

        $this->assertCount(1, $results);
    }

    public function test_it_can_return_ids()
    {
        $results = $this->orderContext->repos()->orderGridRepository()->getOrderIds();

        $this->assertCount(1, $results);
    }

    public function test_it_can_filter_by_order_reference()
    {
        $results = $this->orderContext->repos()->orderGridRepository()
            ->filterByOrderReference('order-aaa')
            ->getResults();

        $this->assertCount(1, $results);

        $results = $this->orderContext->repos()->orderGridRepository()
            ->filterByOrderReference('unknown')
            ->getResults();

        $this->assertCount(0, $results);
    }

    public function test_it_can_filter_by_shopper_email()
    {
        $results = $this->orderContext->repos()->orderGridRepository()
            ->filterByShopperEmail('order-aaa-shopper-aaa@thinktomorrow.be')
            ->getResults();

        $this->assertCount(1, $results);

        $results = $this->orderContext->repos()->orderGridRepository()
            ->filterByShopperEmail('unknown')
            ->getResults();

        $this->assertCount(0, $results);
    }

    public function test_it_can_filter_by_customer_id()
    {
        // Attach customer id to order
        $customer = $this->orderContext->createCustomer();
        $this->orderContext->addCustomerToOrder($this->order, $customer);

        $results = $this->orderContext->repos()->orderGridRepository()
            ->filterByCustomerId('customer-aaa')
            ->getResults();

        $this->assertCount(1, $results);

        $results = $this->orderContext->repos()->orderGridRepository()
            ->filterByCustomerId('unknown')
            ->getResults();

        $this->assertCount(0, $results);
    }

    public function test_it_can_filter_by_order_states()
    {
        $this->assertCount(1, $this->orderContext->repos()->orderGridRepository()->filterByStates([DefaultOrderState::cart_pending->value])->getResults());
        $this->assertCount(0, $this->orderContext->repos()->orderGridRepository()->filterByStates([DefaultOrderState::cancelled->value])->getResults());
    }

    public function test_it_can_filter_by_confirmed_at()
    {
        $this->assertCount(0, $this->orderContext->repos()->orderGridRepository()->filterByConfirmedAt(now()->subDay()->toDateString(), now()->addDay()->toDateString())->getResults());

        // Force timestamp
        $this->updateRow('order-aaa', ['confirmed_at' => now()->toDateTimeString()]);

        $this->assertCount(1, $this->orderContext->repos()->orderGridRepository()->filterByConfirmedAt(now()->subDay()->toDateTimeString(), now()->addDay()->toDateTimeString())->getResults());
        $this->assertCount(1, $this->orderContext->repos()->orderGridRepository()->filterByConfirmedAt(now()->subDay()->toDateString(), now()->addDay()->toDateString())->getResults());
        $this->assertCount(0, $this->orderContext->repos()->orderGridRepository()->filterByConfirmedAt(now()->addDay()->toDateTimeString())->getResults());
        $this->assertCount(0, $this->orderContext->repos()->orderGridRepository()->filterByConfirmedAt(null, now()->subDay()->toDateTimeString())->getResults());
    }

    public function test_it_can_filter_by_delivered_at()
    {
        $this->assertCount(0, $this->orderContext->repos()->orderGridRepository()->filterByDeliveredAt(now()->subDay()->toDateString(), now()->addDay()->toDateString())->getResults());

        // Force timestamp
        $this->updateRow('order-aaa', ['delivered_at' => now()->toDateTimeString()]);

        $this->assertCount(1, $this->orderContext->repos()->orderGridRepository()->filterByDeliveredAt(now()->subDay()->toDateTimeString(), now()->addDay()->toDateTimeString())->getResults());
        $this->assertCount(1, $this->orderContext->repos()->orderGridRepository()->filterByDeliveredAt(now()->subDay()->toDateString(), now()->addDay()->toDateString())->getResults());
        $this->assertCount(0, $this->orderContext->repos()->orderGridRepository()->filterByDeliveredAt(now()->addDay()->toDateTimeString())->getResults());
        $this->assertCount(0, $this->orderContext->repos()->orderGridRepository()->filterByDeliveredAt(null, now()->subDay()->toDateTimeString())->getResults());
    }

    public function test_it_can_sort_by_confirmed_at()
    {
        $this->runTestSortingByDate('confirmed_at', 'sortByConfirmedAt', 'getConfirmedAt');
    }

    public function test_it_can_sort_descending_by_confirmed_at()
    {
        $this->runTestSortingByDate('confirmed_at', 'sortByConfirmedAtDesc', 'getConfirmedAt');
    }

    public function test_it_can_sort_by_delivered_at()
    {
        $this->runTestSortingByDate('delivered_at', 'sortByDeliveredAt', 'getDeliveredAt');
    }

    public function test_it_can_sort_descending_by_delivered_at()
    {
        $this->runTestSortingByDate('delivered_at', 'sortByDeliveredAtDesc', 'getDeliveredAt');
    }

    private function runTestSortingByDate(string $column, string $sortingMethod, string $modelMethod)
    {
        // Add second order with different timestamp to test sorting
        $order2 = $this->orderContext->createDefaultOrder('order-bbb');

        $this->updateRow('order-aaa', [$column => now()->addHour()->toDateTimeString()]);
        $this->updateRow('order-bbb', [$column => now()->toDateTimeString()]);

        $results = $this->orderContext->repos()->orderGridRepository()->{$sortingMethod}()->getResults();

        $this->assertCount(2, $results);

        $previousDatetime = null;
        foreach ($results as $gridItem) {
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

    private function updateRow(string $orderId, array $values)
    {
        DB::table('trader_orders')->where('order_id', $orderId)->update($values);
    }
}
