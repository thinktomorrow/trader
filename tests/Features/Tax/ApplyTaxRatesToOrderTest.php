<?php

namespace Thinktomorrow\Trader\Tests\Features;

use Money\Money;
use Thinktomorrow\Trader\Common\Domain\Price\Percentage;
use Thinktomorrow\Trader\Orders\Domain\Item;
use Thinktomorrow\Trader\Orders\Domain\PurchasableId;
use Thinktomorrow\Trader\Orders\Ports\Persistence\InMemoryOrderRepository;
use Thinktomorrow\Trader\Tax\Application\ApplyTaxRatesToOrder;
use Thinktomorrow\Trader\Tax\Domain\TaxId;
use Thinktomorrow\Trader\Tax\Domain\TaxRate;
use Thinktomorrow\Trader\Tax\Ports\Persistence\InMemoryTaxRateRepository;
use Thinktomorrow\Trader\Tests\Unit\Stubs\PurchasableStub;
use Thinktomorrow\Trader\Tests\Unit\UnitTestCase;

class ApplyTaxRatesToOrderTest extends UnitTestCase
{
    /** @test */
    public function it_can_alter_order_taxrates_to_meet_business_rules()
    {
        $order = $this->makeOrder();
        $purchasable = new PurchasableStub(20, [], Money::EUR(240), Percentage::fromPercent(20));
        $purchasable->setTaxId(3);
        $order->items()->add(Item::fromPurchasable($purchasable));

        // Add order to persistence
        (new InMemoryOrderRepository())->add($order);

        // Add taxrates to persistence
        $taxRate = new TaxRate(TaxId::fromInteger(3), 'foobar', Percentage::fromPercent(10));
        (new InMemoryTaxRateRepository())->add($taxRate);

        (new ApplyTaxRatesToOrder(new InMemoryOrderRepository(), new InMemoryTaxRateRepository()))->handle($order->id());

        $this->assertEquals(Percentage::fromPercent(10), $order->items()->find(PurchasableId::fromInteger(20))->taxRate());
        //$this->assertEquals(Money::EUR(240)->multiply(0.8), $order->total());
    }
}
