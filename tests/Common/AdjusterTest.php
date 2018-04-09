<?php

namespace Thinktomorrow\Trader\Tests\Common;

use Money\Money;
use Thinktomorrow\Trader\Common\Adjusters\Amount;
use Thinktomorrow\Trader\Common\Adjusters\Percentage;
use Thinktomorrow\Trader\Common\Price\Percentage as PercentageValue;
use Thinktomorrow\Trader\Tests\TestCase;

class AdjusterTest extends TestCase
{
   /** @test */
   function it_can_create_an_adjuster()
   {
       $adjuster = new Amount();
       $this->assertInstanceOf(Amount::class, $adjuster);
   }

    /** @test */
    function it_can_get_the_type()
    {
        // Guesses by className
        $adjuster = new Amount();
        $this->assertEquals('amount', $adjuster->getType());

        // Given as parameter
        $adjuster = new Amount('foobar');
        $this->assertEquals('foobar', $adjuster->getType());
    }

    /** @test */
    function it_can_handle_parameters()
    {
        $adjuster1 = (new Amount())->setParameters(Money::EUR(50));
        $adjuster2 = (new Amount())->setParameters(['amount' => Money::EUR(50)]);
        $adjuster3 = (new Amount())->setRawParameters(50);

        $this->assertEquals($adjuster1, $adjuster2);
        $this->assertEquals($adjuster1, $adjuster3);
    }

    /** @test */
    function invalid_parameter_gives_exception()
    {
        $this->expectException(\InvalidArgumentException::class);

        (new Percentage())->setParameters(Money::EUR(50));
    }

    /** @test */
    function it_can_get_parameter_by_key()
    {
        $parameter = PercentageValue::fromPercent(25);
        $adjuster = (new Percentage())->setParameters($parameter);

        $this->assertSame($parameter, $adjuster->getParameter('percentage'));
        $this->assertSame($parameter, $adjuster->getParameter());
    }

    /** @test */
    function it_can_get_raw_parameter_by_key()
    {
        $parameter = PercentageValue::fromPercent(25);
        $adjuster = (new Percentage())->setParameters($parameter);

        $this->assertEquals(25, $adjuster->getRawParameter('percentage'));
        $this->assertEquals(25, $adjuster->getRawParameter());
    }

}
