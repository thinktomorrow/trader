<?php
declare(strict_types=1);

namespace Tests\Acceptance\Cart;

use Thinktomorrow\Trader\Application\Cart\RefreshCart\RefreshCart;
use Thinktomorrow\Trader\Domain\Model\Order\Exceptions\OrderAlreadyInMerchantHands;
use Thinktomorrow\Trader\Domain\Model\Order\OrderId;
use Thinktomorrow\Trader\Domain\Model\Order\State\DefaultOrderState;
use Thinktomorrow\Trader\Domain\Model\Product\ProductId;
use Thinktomorrow\Trader\Domain\Model\Product\Variant\VariantState;
use Thinktomorrow\Trader\Domain\Model\Product\Variant\VariantUnitPrice;

class RefreshCartTest extends CartContext
{
    protected function setUp(): void
    {
        parent::setUp();
    }

    public function test_it_can_refresh_order()
    {
        $this->givenThereIsAProductWhichCostsEur('aaa', 5);
        $this->whenIAddTheVariantToTheCart('aaa-123', 2);

        $this->cartApplication->refresh(new RefreshCart('xxx'));

        $cart = $this->cartRepository->findCart(OrderId::fromString('xxx'));
        $this->assertEquals('€ 10', $cart->getTotalPrice());
    }

    public function test_it_cannot_refresh_cart_when_order_is_no_longer_is_shopper_hands()
    {
        $this->givenThereIsAProductWhichCostsEur('aaa', 5);
        $this->whenIAddTheVariantToTheCart('aaa-123', 2);

        // Force a merchant state
        $order = $this->getOrder();
        $order->updateState(DefaultOrderState::confirmed);
        $this->orderRepository->save($order);

        $this->updateVariant();

        $this->expectException(OrderAlreadyInMerchantHands::class);
        $this->cartApplication->refresh(new RefreshCart('xxx'));

        $cart = $this->cartRepository->findCart(OrderId::fromString('xxx'));
        $this->assertEquals('€ 10', $cart->getTotalPrice());
    }

    public function test_it_can_refresh_variant_prices()
    {
        $this->givenThereIsAProductWhichCostsEur('aaa', 5);
        $this->whenIAddTheVariantToTheCart('aaa-123', 2);

        // Check unchanged line first
        $cart = $this->cartRepository->findCart(OrderId::fromString('xxx'));
        $this->assertEquals('€ 10', $cart->getTotalPrice());

        $this->updateVariant();

        $this->cartApplication->refresh(new RefreshCart('xxx'));

        $cart = $this->cartRepository->findCart(OrderId::fromString('xxx'));
        $this->assertEquals('€ 20', $cart->getTotalPrice());
    }

    public function test_it_can_refresh_variant_availability()
    {
        $this->givenThereIsAProductWhichCostsEur('aaa', 5);
        $this->whenIAddTheVariantToTheCart('aaa-123', 2);

        // Check unchanged line first
        $cart = $this->cartRepository->findCart(OrderId::fromString('xxx'));
        $this->assertEquals('€ 10', $cart->getTotalPrice());
        $this->assertEquals(1, $cart->getSize());

        $this->updateVariant(VariantState::unavailable);

        $this->cartApplication->refresh(new RefreshCart('xxx'));

        $cart = $this->cartRepository->findCart(OrderId::fromString('xxx'));
        $this->assertEquals('€ 0', $cart->getTotalPrice());
        $this->assertEquals(0, $cart->getSize());
    }

    public function test_it_can_refresh_tax_rates()
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

        $this->cartApplication->refresh(new RefreshCart('xxx'));

        $cart = $this->cartRepository->findCart(OrderId::fromString('xxx'));
        $lineTaxRate = $cart->getLines()[0]->getLinePriceAsPrice()->getVatPercentage();
        $this->assertEquals('10', $lineTaxRate->get());
    }

    public function test_it_can_refresh_tax_rates_by_base_mapping()
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
        $this->assertEquals('21', $lineTaxRate->get());

        // Change billing country to NL
        $this->givenOrderHasABillingCountry('NL');

        $this->cartApplication->refresh(new RefreshCart('xxx'));

        $cart = $this->cartRepository->findCart(OrderId::fromString('xxx'));
        $lineTaxRate = $cart->getLines()[0]->getLinePriceAsPrice()->getVatPercentage();
        $this->assertEquals('15', $lineTaxRate->get());
    }

    public function test_it_does_not_change_tax_rates_when_billing_country_does_not_belong_to_taxrate_profile()
    {
        $this->givenThereIsAVatRate(['20' => '10'], ['NL']);
        $this->givenThereIsAProductWhichCostsEur('aaa', 5);
        $this->whenIAddTheVariantToTheCart('aaa-123', 1);

        // Check unchanged line first
        $cart = $this->cartRepository->findCart(OrderId::fromString('xxx'));
        $lineTaxRate = $cart->getLines()[0]->getLinePriceAsPrice()->getVatPercentage();
        $this->assertEquals('20', $lineTaxRate->get());

        $this->givenOrderHasABillingCountry('FR');

        $this->cartApplication->refresh(new RefreshCart('xxx'));

        $cart = $this->cartRepository->findCart(OrderId::fromString('xxx'));
        $lineTaxRate = $cart->getLines()[0]->getLinePriceAsPrice()->getVatPercentage();
        $this->assertEquals('20', $lineTaxRate->get());
    }

    public function test_it_can_refresh_shipping_cost_tax_rates()
    {
        $this->givenOrderHasAShippingCountry('BE');
        $this->givenShippingCostsForAPurchaseOfEur('50', 0, 1000);
        $this->givenThereIsAProductWhichCostsEur('aaa', 5);
        $this->whenIAddTheVariantToTheCart('aaa-123', 1);
        $this->whenIChooseShipping('bpost_home');

        // Apply shipping
        $this->cartApplication->refresh(new RefreshCart('xxx'));

        // Check unchanged cost first
        $order = $this->orderRepository->find(OrderId::fromString('xxx'));
        $this->assertEquals('10', $order->getShippings()[0]->getShippingCost()->getVatPercentage()->get());

        // Change billing country to NL
        $this->givenThereIsAVatRate(['10' => '25'], ['NL']);
        $this->givenOrderHasABillingCountry('NL');

        $this->cartApplication->refresh(new RefreshCart('xxx'));

        $order = $this->orderRepository->find(OrderId::fromString('xxx'));
        $this->assertEquals('25', $order->getShippings()[0]->getShippingCost()->getVatPercentage()->get());

    }

    public function test_it_can_refresh_discounts()
    {
    }

    public function test_it_can_refresh_shipping_profile_cost()
    {
    }

    public function test_it_can_refresh_payment_method_cost()
    {
    }

    public function test_it_can_find_cart_without_variant_when_variant_is_no_longer_present()
    {
        // TODO: this should be detected by refresh job of the order. Triggered by variant
    }

    private function updateVariant(?VariantState $state = null): void
    {
        $product = $this->productRepository->find(ProductId::fromString('aaa'));
        $variant = $product->getVariants()[0];
        $variant->updatePrice(VariantUnitPrice::fromPrice($variant->getSalePrice()), $variant->getSalePrice()->multiply(2));

        if ($state) {
            $variant->updateState($state);
        }

        $product->updateVariant($variant);
    }
}
