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
        $this->givenThereIsAProductWhichCostsEur('aaa', 5);
        $this->whenIAddTheVariantToTheCart('aaa-123', 1);

        // Check unchanged line first
        $cart = $this->cartRepository->findCart(OrderId::fromString('xxx'));
        $lineTaxRate = $cart->getLines()[0]->getLinePriceAsPrice()->getVatPercentage();
        $this->assertEquals('20', $lineTaxRate->get());

        // Change billing country to NL
        $this->givenOrderHasABillingCountry('NL');

        // Reset memoized vat
        $this->resetMemoizedVatPercentages();

        $this->cartApplication->refresh(new RefreshCart('xxx'));

        $cart = $this->cartRepository->findCart(OrderId::fromString('xxx'));
        $lineTaxRate = $cart->getLines()[0]->getLinePriceAsPrice()->getVatPercentage();
        $this->assertEquals('10', $lineTaxRate->get());
    }

    public function test_it_can_refresh_vat_rates_by_base_mapping()
    {
        $primaryVatRate = $this->givenThereIsAVatRate('BE', '21');
        $nlVatRate = $this->givenThereIsAVatRate('NL', '10');
        $nlVatRate2 = $this->givenThereIsAVatRate('NL', '15');
        $this->givenVatRateHasBaseRateOf($nlVatRate2->vatRateId, $primaryVatRate->vatRateId);

        $this->givenThereIsAProductWhichCostsEur('aaa', 5);
        $this->whenIAddTheVariantToTheCart('aaa-123', 1);

        // Check unchanged line first
        $cart = $this->cartRepository->findCart(OrderId::fromString('xxx'));
        $lineTaxRate = $cart->getLines()[0]->getLinePriceAsPrice()->getVatPercentage();
        $this->assertEquals('20', $lineTaxRate->get());

        // Change billing country to NL
        $this->givenOrderHasABillingCountry('NL');

        // Reset memoized vat
        $this->resetMemoizedVatPercentages();

        $this->cartApplication->refresh(new RefreshCart('xxx'));

        $cart = $this->cartRepository->findCart(OrderId::fromString('xxx'));
        $lineTaxRate = $cart->getLines()[0]->getLinePriceAsPrice()->getVatPercentage();
        $this->assertEquals('15', $lineTaxRate->get());
    }

    public function test_it_does_not_change_vat_rates_when_billing_country_does_not_have_vat_rates()
    {
        $this->givenThereIsAVatRate('NL', '10');
        $this->givenThereIsAProductWhichCostsEur('aaa', 5);
        $this->whenIAddTheVariantToTheCart('aaa-123', 1);

        // Check unchanged line first
        $cart = $this->cartRepository->findCart(OrderId::fromString('xxx'));
        $lineTaxRate = $cart->getLines()[0]->getLinePriceAsPrice()->getVatPercentage();
        $this->assertEquals('20', $lineTaxRate->get());

        $this->givenOrderHasABillingCountry('FR');

        // Reset memoized vat
        $this->resetMemoizedVatPercentages();

        $this->cartApplication->refresh(new RefreshCart('xxx'));
        $cart = $this->cartRepository->findCart(OrderId::fromString('xxx'));

        $lineTaxRate = $cart->getLines()[0]->getLinePriceAsPrice()->getVatPercentage();
        $this->assertEquals('20', $lineTaxRate->get());
    }

    public function test_it_can_refresh_shipping_cost_vat_rates()
    {
        $this->givenOrderHasAShippingCountry('BE');
        $this->givenShippingCostsForAPurchaseOfEur('50', 0, 1000);
        $this->givenThereIsAProductWhichCostsEur('aaa', 5);
        $this->whenIAddTheVariantToTheCart('aaa-123', 1);
        $this->whenIChooseShipping('bpost_home');

        //        // Apply shipping
        $this->cartApplication->refresh(new RefreshCart('xxx'));

        // Check unchanged cost first
        $order = $this->orderRepository->find(OrderId::fromString('xxx'));
        $this->assertEquals('21', $order->getShippings()[0]->getShippingCost()->getVatPercentage()->get());

        // Change billing country to NL
        $nlVat = $this->givenThereIsAVatRate('NL', '25');
        $this->givenOrderHasABillingCountry('NL');

        // Reset memoized vat
        $this->resetMemoizedVatPercentages();

        $this->cartApplication->refresh(new RefreshCart('xxx'));
        $order = $this->orderRepository->find(OrderId::fromString('xxx'));

        $this->assertEquals($nlVat->getRate(), $order->getShippings()[0]->getShippingCost()->getVatPercentage());
    }
}
