<?php

namespace Thinktomorrow\Trader\Tests\Unit;

use PHPUnit_Framework_TestCase;
use Thinktomorrow\Trader\Discounts\Domain\Conditions\MinimumAmount;
use Thinktomorrow\Trader\Discounts\Domain\DiscountId;
use Thinktomorrow\Trader\Discounts\Domain\Types\PercentageOffDiscount;
use Thinktomorrow\Trader\Discounts\Domain\Types\PercentageOffItemDiscount;
use Thinktomorrow\Trader\Order\Domain\Order;
use Thinktomorrow\Trader\Order\Domain\OrderId;
use Thinktomorrow\Trader\Price\Percentage;
use Thinktomorrow\Trader\Tests\DummyContainer;

class UnitTestCase extends PHPUnit_Framework_TestCase
{
    public function setUp()
    {
        parent::setUp();

        // Container setup - this is the setup that should be done in the framework
        // TODO: should also mean that our containerinterface
        $container = new DummyContainer();
        //$container->add();

    }

    protected function makeOrder()
    {
        return new Order(OrderId::fromInteger(2));
    }

    protected function makePercentageOffDiscount($id = 1, $conditions = [], $adjusters = [])
    {
        if(empty($adjusters))
        {
            $adjusters = [
                'percentage' => Percentage::fromPercent(10)
            ];
        }

        return new PercentageOffDiscount(DiscountId::fromInteger($id),$conditions,$adjusters);
    }

    protected function makePercentageOffItemDiscount($id = 1, $conditions = [], $adjusters = [])
    {
        if(empty($adjusters))
        {
            $adjusters = [
                'percentage' => Percentage::fromPercent(10)
            ];
        }

        return new PercentageOffItemDiscount(DiscountId::fromInteger($id),$conditions,$adjusters);
    }
}