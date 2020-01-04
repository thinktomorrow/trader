<?php

namespace Thinktomorrow\Trader\TestsOld\Tax;

use Thinktomorrow\Trader\Common\Price\Percentage;
use Thinktomorrow\Trader\Countries\CountryId;
use Thinktomorrow\Trader\Tax\Domain\CountryRate;
use Thinktomorrow\Trader\Tax\Domain\Rules\BillingCountryRule;
use Thinktomorrow\Trader\Tax\Domain\Rules\ForeignBusinessRule;
use Thinktomorrow\Trader\Tax\Domain\TaxId;
use Thinktomorrow\Trader\Tax\Domain\TaxRate;
use Thinktomorrow\Trader\TestsOld\TestCase;

class TaxRateTest extends TestCase
{
    /** @test */
    public function it_can_return_the_rate_percentage()
    {
        $rate = new TaxRate(TaxId::fromInteger(1), 'foobar', Percentage::fromPercent(21));

        $this->assertEquals('foobar', $rate->name());
        $this->assertEquals(Percentage::fromPercent(21), $rate->get());
    }

    /** @test */
    public function given_country_rate_can_override_the_standard_rate()
    {
        $order = $this->makeOrder();
        $order->setBillingAddress(['country_key' => 'NL']);

        $rate = new TaxRate(TaxId::fromInteger(1), 'foobar', Percentage::fromPercent(21), [
            new CountryRate('NL', Percentage::fromPercent(10), CountryId::fromIsoString('NL')),
        ], [
            $this->container(BillingCountryRule::class),
        ]);

        $this->assertEquals(Percentage::fromPercent(10), $rate->get(null, $order));
    }

    /** @test */
    public function it_can_set_rate_to_zero_for_business_from_other_country_than_merchant()
    {
        $order = $this->makeOrder()->setBusiness()->setBillingAddress(['country_key' => 'NL']);
        $rate = new TaxRate(TaxId::fromInteger(1), 'foobar', Percentage::fromPercent(21), [], [
            $this->container(ForeignBusinessRule::class),
        ]);

        $this->assertEquals(Percentage::fromPercent(0), $rate->setMerchantCountry(CountryId::fromIsoString('BE'))->get(null, $order));
    }

    /** @test */
    public function business_inside_same_country_as_merchant_has_to_pay_tax()
    {
        $order = $this->makeOrder()->setBusiness()->setBillingAddress(['country_key' => 'BE']);
        $rate = new TaxRate(TaxId::fromInteger(1), 'foobar', Percentage::fromPercent(21), [], [
            $this->container(BillingCountryRule::class),
            $this->container(ForeignBusinessRule::class),
        ]);

        $this->assertEquals(Percentage::fromPercent(21), $rate->setMerchantCountry(CountryId::fromIsoString('BE'))->get(null, $order));
    }
}
