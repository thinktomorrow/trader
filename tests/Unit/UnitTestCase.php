<?php

namespace Thinktomorrow\Trader\Tests\Unit;

use Money\Money;
use PHPUnit_Framework_TestCase;
use Thinktomorrow\Trader\Discounts\Domain\DiscountFactory;
use Thinktomorrow\Trader\Discounts\Domain\DiscountId;
use Thinktomorrow\Trader\Discounts\Domain\Types\PercentageOffDiscount;
use Thinktomorrow\Trader\Order\Domain\Item;
use Thinktomorrow\Trader\Order\Domain\Order;
use Thinktomorrow\Trader\Order\Domain\OrderId;
use Thinktomorrow\Trader\Common\Price\Percentage;
use Thinktomorrow\Trader\Tests\DummyContainer;
use Thinktomorrow\Trader\Tests\Unit\Stubs\ConcretePurchasable;

class UnitTestCase extends PHPUnit_Framework_TestCase
{
    public function setUp()
    {
        parent::setUp();
    }

    protected function makeOrder($subtotalAmount = 0, $id = 2)
    {
        $order = new Order(OrderId::fromInteger($id));

        if($subtotalAmount > 0) $order->items()->add(Item::fromPurchasable(new ConcretePurchasable(20,[],Money::EUR($subtotalAmount))));

        return $order;
    }

    protected function makeDiscount($id = 1, $type =  'percentage_off', $conditions = [], $adjusters = [])
    {
        if(empty($adjusters) && $type == 'percentage_off')
        {
            $adjusters = [
                'percentage' => Percentage::fromPercent(10)
            ];
        }

        return (new DiscountFactory(new DummyContainer()))->create($id,$type,$conditions,$adjusters);
    }

    protected function makePercentageOffDiscount($percent = 10)
    {
        $adjusters = [
            'percentage' => Percentage::fromPercent($percent)
        ];

        return $this->makeDiscount(1, 'percentage_off', [], $adjusters);
    }
}