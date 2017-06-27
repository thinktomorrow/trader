<?php

namespace Thinktomorrow\Trader\Tests\Unit;

use PHPUnit_Framework_TestCase;
use Thinktomorrow\Trader\Order\Domain\Order;
use Thinktomorrow\Trader\Order\Domain\OrderId;

class UnitTestCase extends PHPUnit_Framework_TestCase
{
    protected function makeOrder()
    {
        return new Order(OrderId::fromInteger(2));
    }
}