<?php

namespace Thinktomorrow\Trader\Tests;

use Thinktomorrow\Trader\Common\Domain\Price\Percentage;
use Thinktomorrow\Trader\Countries\CountryId;
use Thinktomorrow\Trader\Tax\Domain\CountryRate;
use Thinktomorrow\Trader\Tax\Domain\StandardRate;
use Thinktomorrow\Trader\Tests\Unit\UnitTestCase;

class StandardRateTest extends UnitTestCase
{
    /** @test */
    function it_can_return_the_rate_percentage()
    {
        $rate = new StandardRate('foobar', Percentage::fromPercent(21));

        $this->assertEquals('foobar',$rate->name());
        $this->assertEquals(Percentage::fromPercent(21),$rate->get());
    }

    /** @test */
    function given_country_rate_can_override_the_standard_rate()
    {
        $rate = new StandardRate('foobar', Percentage::fromPercent(21),[
            new CountryRate('NL',Percentage::fromPercent(10), CountryId::fromIsoString('NL'))
        ]);

        $this->assertEquals(Percentage::fromPercent(10),$rate->forBillingCountry(CountryId::fromIsoString('NL'))->get());
    }

    /** @test */
    function business_inside_sender_country_has_to_pay_tax()
    {
        $rate = new StandardRate('foobar', Percentage::fromPercent(21),[]);
        $rate->fromCountry(CountryId::fromIsoString('BE'));

        $this->assertEquals(Percentage::fromPercent(21),
            $rate->forBusiness()
                ->forBillingCountry(CountryId::fromIsoString('BE'))
                ->get()
        );
    }

    /** @test */
    function it_can_set_rate_to_zero_for_business_outside_of_sender_country()
    {
        $rate = new StandardRate('foobar', Percentage::fromPercent(21),[]);
        $rate->fromCountry(CountryId::fromIsoString('BE'));

        $this->assertEquals(Percentage::fromPercent(0),
            $rate->forBusiness()
                 ->forBillingCountry(CountryId::fromIsoString('NL'))
                 ->get()
        );
    }

    /** @test */
//    function it_can_set_rate_to_zero_for_consumers_outside_of_europe()
//    {
//        $rate = new StandardRate('foobar', Percentage::fromPercent(21),[]);
//        $rate->fromCountry(CountryId::fromIsoString('BE'));
//
//        $this->assertEquals(Percentage::fromPercent(0),
//            $rate->forBillingCountry(CountryId::fromIsoString('US'))
//                ->get()
//        );
//    }
}