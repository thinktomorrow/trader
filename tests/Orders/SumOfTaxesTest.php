<?php

namespace Thinktomorrow\Trader\Tests;

use Money\Money;
use Thinktomorrow\Trader\Common\Price\Percentage;
use Thinktomorrow\Trader\Orders\Domain\Services\SumOfTaxes;
use Thinktomorrow\Trader\Tests\Stubs\PurchasableStub;

class SumOfTaxesTest extends TestCase
{
    /** @test */
    public function one_tax_has_only_one_entry()
    {
        $order = $this->getCleanOrder();
        $order->items()->add($this->getItem(null, Percentage::fromPercent(10), new PurchasableStub(20, [], Money::EUR(100))));

        $sum = new SumOfTaxes();
        $taxes = $sum->forOrder($order);

        $this->assertCount(1, $taxes);
        $this->assertEquals(Percentage::fromPercent(10), $taxes[10]['percent']);
        $this->assertEquals(Money::EUR(10), $taxes[10]['tax']);
    }

    /** @test */
    public function tax_are_grouped_per_rate()
    {
        $order = $this->getCleanOrder();
        $order->items()->add($this->getItem(null, Percentage::fromPercent(10), new PurchasableStub(20, [], Money::EUR(100))));
        $order->items()->add($this->getItem(null, Percentage::fromPercent(10), new PurchasableStub(40, [], Money::EUR(200))));

        $sum = new SumOfTaxes();
        $taxes = $sum->forOrder($order);

        $this->assertEquals(Money::EUR(30), $taxes[10]['tax']);
    }

    /** @test */
    public function multiple_rates_have_multiple_entries()
    {
        $order = $this->getCleanOrder();
        $order->items()->add($this->getItem(null, Percentage::fromPercent(10), new PurchasableStub(20, [], Money::EUR(100))));
        $order->items()->add($this->getItem(null, Percentage::fromPercent(12), new PurchasableStub(40, [], Money::EUR(100), Percentage::fromPercent(30)))); // Item specific taxrate has precedence

        $sum = new SumOfTaxes();
        $taxes = $sum->forOrder($order);

        $this->assertCount(2, $taxes);
        $this->assertEquals(Percentage::fromPercent(10), $taxes[10]['percent']);
        $this->assertEquals(Percentage::fromPercent(12), $taxes[12]['percent']);
        $this->assertEquals(Money::EUR(10), $taxes[10]['tax']);
        $this->assertEquals(Money::EUR(12), $taxes[12]['tax']);
    }

    /** @test */
    public function global_taxes_have_the_default_taxrate()
    {
        $order = $this->getCleanOrder();
        $order->items()->add($this->getItem(null, Percentage::fromPercent(21), new PurchasableStub(20, [], Money::EUR(100), Percentage::fromPercent(21))));
        $order->setPaymentTotal(Money::EUR(30));
        $order->setShippingTotal(Money::EUR(20));

        $order->setTaxPercentage(Percentage::fromPercent(6));

        $sum = new SumOfTaxes();
        $taxes = $sum->forOrder($order);

        $this->assertCount(2, $taxes);
        $this->assertEquals(Percentage::fromPercent(21), $taxes[21]['percent']);
        $this->assertEquals(Percentage::fromPercent(6), $taxes[6]['percent']);
        $this->assertEquals(Money::EUR(21), $taxes[21]['tax']);
        $this->assertEquals(Money::EUR(3), $taxes[6]['tax']);
    }

    /** @test */
    public function global_taxes_are_added_to_existing_rate_if_present()
    {
        $order = $this->getCleanOrder();
        $order->items()->add($this->getItem(null, Percentage::fromPercent(21), new PurchasableStub(20, [], Money::EUR(100), Percentage::fromPercent(21))));
        $order->setPaymentTotal(Money::EUR(30));
        $order->setShippingTotal(Money::EUR(20));

        $order->setTaxPercentage(Percentage::fromPercent(21));

        $sum = new SumOfTaxes();
        $taxes = $sum->forOrder($order);

        $this->assertCount(1, $taxes);
        $this->assertEquals(Percentage::fromPercent(21), $taxes[21]['percent']);
        $this->assertEquals(Money::EUR(32), $taxes[21]['tax']); // rounding from 31.5
    }
}
