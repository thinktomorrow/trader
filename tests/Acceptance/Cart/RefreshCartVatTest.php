<?php

declare(strict_types=1);

namespace Tests\Acceptance\Cart;

use Thinktomorrow\Trader\Application\Cart\RefreshCart\RefreshCart;
use Thinktomorrow\Trader\Domain\Model\Order\OrderId;

class RefreshCartVatTest extends CartContext
{
    protected function setUp(): void
    {
        parent::setUp();
    }

    public function test_it_can_refresh_vat_rates()
    {
        $nlVatRate = $this->givenThereIsAVatRate('NL', '10');
        $this->givenThereIsAProductWhichCostsEur('product-aaa', 5);
        $this->whenIAddTheVariantToTheCart('product-aaa-variant-aaa', 1);

        // Check unchanged line first
        $cart = $this->orderContext->repos()->cartRepository()->findCart(OrderId::fromString('xxx'));
        $lineTaxRate = $cart->getLines()[0]->getVatPercentage();
        $this->assertEquals('20', $lineTaxRate->get());

        // Change billing country to NL
        $this->givenOrderHasABillingCountry('NL');

        $this->orderContext->apps()->cartApplication()->refresh(new RefreshCart('xxx'));

        $cart = $this->orderContext->repos()->cartRepository()->findCart(OrderId::fromString('xxx'));
        $lineTaxRate = $cart->getLines()[0]->getVatPercentage();
        $this->assertEquals('10', $lineTaxRate->get());
    }

    public function test_it_can_refresh_vat_rates_by_base_mapping()
    {
        $primaryVatRate = $this->givenThereIsAVatRate('BE', '21');
        $nlVatRate = $this->givenThereIsAVatRate('NL', '10');
        $nlVatRate2 = $this->givenThereIsAVatRate('NL', '15');
        $this->givenVatRateHasBaseRateOf($nlVatRate2->vatRateId, $primaryVatRate->vatRateId);

        $this->givenThereIsAProductWhichCostsEur('aaa', 5);
        $this->whenIAddTheVariantToTheCart('aaa-variant-aaa', 1);

        // Check unchanged line first
        $cart = $this->orderContext->repos()->cartRepository()->findCart(OrderId::fromString('xxx'));
        $lineTaxRate = $cart->getLines()[0]->getVatPercentage();
        $this->assertEquals('20', $lineTaxRate->get());

        // Change billing country to NL
        $this->givenOrderHasABillingCountry('NL');

        $this->orderContext->apps()->cartApplication()->refresh(new RefreshCart('xxx'));

        $cart = $this->orderContext->repos()->cartRepository()->findCart(OrderId::fromString('xxx'));
        $lineTaxRate = $cart->getLines()[0]->getVatPercentage();
        $this->assertEquals('15', $lineTaxRate->get());
    }

    public function test_it_does_not_change_vat_rates_when_billing_country_does_not_have_vat_rates()
    {
        $this->givenThereIsAVatRate('NL', '10');
        $this->givenThereIsAProductWhichCostsEur('aaa', 5);
        $this->whenIAddTheVariantToTheCart('aaa-variant-aaa', 1);

        // Check unchanged line first
        $cart = $this->orderContext->repos()->cartRepository()->findCart(OrderId::fromString('xxx'));
        $lineTaxRate = $cart->getLines()[0]->getVatPercentage();
        $this->assertEquals('20', $lineTaxRate->get());

        $this->givenOrderHasABillingCountry('FR');

        $this->orderContext->apps()->cartApplication()->refresh(new RefreshCart('xxx'));
        $cart = $this->orderContext->repos()->cartRepository()->findCart(OrderId::fromString('xxx'));

        $lineTaxRate = $cart->getLines()[0]->getVatPercentage();
        $this->assertEquals('20', $lineTaxRate->get());
    }
}
