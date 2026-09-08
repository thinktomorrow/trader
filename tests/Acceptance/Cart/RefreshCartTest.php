<?php

declare(strict_types=1);

namespace Tests\Acceptance\Cart;

use Money\Money;
use Thinktomorrow\Trader\Domain\Common\Cash\Cash;
use Thinktomorrow\Trader\Domain\Common\Vat\VatPercentage;
use Thinktomorrow\Trader\Domain\Model\Order\Exceptions\OrderAlreadyInMerchantHands;
use Thinktomorrow\Trader\Domain\Model\Order\OrderId;
use Thinktomorrow\Trader\Domain\Model\Order\State\DefaultOrderState;
use Thinktomorrow\Trader\Domain\Model\PaymentMethod\PaymentMethodId;
use Thinktomorrow\Trader\Domain\Model\PaymentMethod\PaymentMethodState;
use Thinktomorrow\Trader\Domain\Model\Product\ProductId;
use Thinktomorrow\Trader\Domain\Model\Product\Variant\VariantSalePrice;
use Thinktomorrow\Trader\Domain\Model\Product\Variant\VariantState;
use Thinktomorrow\Trader\Domain\Model\Product\Variant\VariantUnitPrice;
use Thinktomorrow\Trader\Domain\Model\ShippingProfile\ShippingProfile;
use Thinktomorrow\Trader\Domain\Model\ShippingProfile\ShippingProfileId;
use Thinktomorrow\Trader\Domain\Model\ShippingProfile\ShippingProfileState;
use Thinktomorrow\Trader\Domain\Model\ShippingProfile\ShippingProviderId;
use Thinktomorrow\Trader\Domain\Model\ShippingProfile\Tariff;

class RefreshCartTest extends CartContext
{
    protected function setUp(): void
    {
        parent::setUp();
    }

    public function test_it_can_refresh_order()
    {
        $this->givenThereIsAProductWhichCostsEur('aaa', 5);
        $this->whenIAddTheVariantToTheCart('aaa-variant-aaa', 2);

        $this->refreshCart();

        $cart = $this->orderContext->repos()->cartRepository()->findCart(OrderId::fromString('xxx'));

        $this->assertEquals('€ 12', $cart->getFormattedTotalIncl());
    }

    public function test_it_cannot_refresh_cart_when_order_is_no_longer_is_shopper_hands()
    {
        $this->givenThereIsAProductWhichCostsEur('aaa', 5);
        $this->whenIAddTheVariantToTheCart('aaa-variant-aaa', 2);

        // Force a merchant state
        $order = $this->getOrder();
        $order->updateState(DefaultOrderState::confirmed);
        $this->orderContext->repos()->orderRepository()->save($order);

        $this->updateVariant();

        $this->expectException(OrderAlreadyInMerchantHands::class);

        $this->refreshCart();

        $cart = $this->orderContext->repos()->cartRepository()->findCart(OrderId::fromString('xxx'));
        $this->assertEquals('€ 10', $cart->getFormattedTotalIncl());
    }

    public function test_it_can_refresh_variant_prices()
    {
        $this->givenThereIsAProductWhichCostsEur('aaa', 5);
        $this->whenIAddTheVariantToTheCart('aaa-variant-aaa', 2);

        // Check unchanged line first
        $this->refreshCart();
        $cart = $this->orderContext->repos()->cartRepository()->findCart(OrderId::fromString('xxx'));
        $this->assertEquals('€ 12', $cart->getFormattedTotalIncl());

        $this->updateVariant();

        $this->refreshCart();

        $cart = $this->orderContext->repos()->cartRepository()->findCart(OrderId::fromString('xxx'));
        $this->assertEquals('€ 24', $cart->getFormattedTotalIncl());
    }

    public function test_it_can_refresh_variant_availability()
    {
        $this->givenThereIsAProductWhichCostsEur('aaa', 5);
        $this->whenIAddTheVariantToTheCart('aaa-variant-aaa', 2);

        // Check unchanged line first
        $this->refreshCart();
        $cart = $this->orderContext->repos()->cartRepository()->findCart(OrderId::fromString('xxx'));
        $this->assertEquals('€ 12', $cart->getFormattedTotalIncl());
        $this->assertEquals(1, $cart->getSize());

        $this->updateVariant(VariantState::unavailable);

        $this->refreshCart();

        $cart = $this->orderContext->repos()->cartRepository()->findCart(OrderId::fromString('xxx'));
        $this->assertEquals('€ 0', $cart->getFormattedTotalIncl());
        $this->assertEquals(0, $cart->getSize());
    }

    public function test_it_applies_line_discounts_before_refreshing_shipping_costs(): void
    {
        $this->catalogContext->createProduct('aaa', null);
        $this->catalogContext->createVariant('aaa', 'aaa-variant-aaa', [
            'unit_price' => 10000,
            'sale_price' => 10000,
        ]);
        $this->whenIAddTheVariantToTheCart('aaa-variant-aaa');

        $shippingProfile = ShippingProfile::create(
            ShippingProfileId::fromString('shipping'),
            ShippingProviderId::fromString('postnl'),
            false,
        );
        $shippingProfile->addTariff(Tariff::create(
            $this->orderContext->repos()->shippingProfileRepository()->nextTariffReference(),
            $shippingProfile->shippingProfileId,
            Money::EUR(200),
            Cash::zero(),
            Money::EUR(8999),
        ));
        $shippingProfile->addTariff(Tariff::create(
            $this->orderContext->repos()->shippingProfileRepository()->nextTariffReference(),
            $shippingProfile->shippingProfileId,
            Money::EUR(500),
            Money::EUR(9000),
            null,
        ));
        $this->orderContext->repos()->shippingProfileRepository()->save($shippingProfile);
        $this->whenIChooseShipping('shipping');
        $this->assertEquals(Money::EUR(500), $this->getOrder()->getShippingCostExcl());

        $product = $this->catalogContext->repos()->productRepository()->find(ProductId::fromString('aaa'));
        $variant = $product->getVariants()[0];
        $variant->updatePrice(
            VariantUnitPrice::fromMoney(Money::EUR(10000), VatPercentage::fromString('20'), false),
            VariantSalePrice::fromMoney(Money::EUR(8000), VatPercentage::fromString('20'), false),
        );
        $product->updateVariant($variant);
        $this->catalogContext->saveProduct($product);

        $this->refreshCart();

        $order = $this->getOrder();
        $this->assertEquals(Money::EUR(2000), $order->getLines()[0]->getDiscountPriceExcl());
        $this->assertEquals(Money::EUR(200), $order->getShippingCostExcl());
    }

    public function test_it_refreshes_the_selected_payment_method_cost(): void
    {
        $this->givenPaymentMethod(10);
        $this->whenIChoosePayment('visa');

        $paymentMethod = $this->orderContext->repos()->paymentMethodRepository()->find(PaymentMethodId::fromString('visa'));
        $paymentMethod->updateRate(Money::EUR(1500));
        $this->orderContext->repos()->paymentMethodRepository()->save($paymentMethod);

        $this->refreshCart();

        $this->assertEquals(Money::EUR(1500), $this->getOrder()->getPaymentCostExcl());
    }

    public function test_refresh_removes_a_selected_payment_method_that_no_longer_exists(): void
    {
        $this->givenPaymentMethod(10);
        $this->whenIChoosePayment('visa');
        $this->orderContext->repos()->paymentMethodRepository()->delete(PaymentMethodId::fromString('visa'));

        $this->refreshCart();

        $this->assertSame([], $this->getOrder()->getPayments());
    }

    public function test_refresh_removes_a_selected_payment_method_that_is_no_longer_available(): void
    {
        $this->givenPaymentMethod(10);
        $this->whenIChoosePayment('visa');

        $paymentMethod = $this->orderContext->repos()->paymentMethodRepository()->find(PaymentMethodId::fromString('visa'));
        $paymentMethod->updateState(PaymentMethodState::offline);
        $this->orderContext->repos()->paymentMethodRepository()->save($paymentMethod);

        $this->refreshCart();

        $this->assertSame([], $this->getOrder()->getPayments());
    }

    public function test_refresh_removes_shipping_profile_that_became_unavailable(): void
    {
        $this->givenShippingCostsForAPurchaseOfEur(2, 0, 10, [], 'shipping');
        $this->whenIChooseShipping('shipping');
        $shippingProfile = $this->orderContext->repos()->shippingProfileRepository()->find(ShippingProfileId::fromString('shipping'));
        $shippingProfile->updateState(ShippingProfileState::offline);
        $this->orderContext->repos()->shippingProfileRepository()->save($shippingProfile);

        $this->refreshCart();

        $cart = $this->orderContext->repos()->cartRepository()->findCart(OrderId::fromString('xxx'));
        $this->assertNull($cart->getShipping());
    }

    public function test_refresh_removes_shipping_profile_that_no_longer_supports_country(): void
    {
        $this->orderContext->createCountry('BE');
        $this->orderContext->createCountry('NL');
        $this->givenShippingCostsForAPurchaseOfEur(2, 0, 10, ['BE'], 'shipping');
        $this->givenOrderHasAShippingCountry('BE');
        $this->whenIChooseShipping('shipping');
        $this->givenOrderHasAShippingCountry('NL');

        $this->refreshCart();

        $cart = $this->orderContext->repos()->cartRepository()->findCart(OrderId::fromString('xxx'));
        $this->assertNull($cart->getShipping());
    }

    //    public function test_it_can_refresh_discounts()
    //    {
    //    }
    //
    //    public function test_it_can_refresh_shipping_profile_cost()
    //    {
    //    }
    //
    //    public function test_it_can_refresh_payment_method_cost()
    //    {
    //    }
    //
    //    public function test_it_can_find_cart_without_variant_when_variant_is_no_longer_present()
    //    {
    //        // TODO: this should be detected by refresh job of the order. Triggered by variant
    //    }

    private function updateVariant(?VariantState $state = null): void
    {
        $product = $this->catalogContext->repos()->productRepository()->find(ProductId::fromString('aaa'));
        $variant = $product->getVariants()[0];

        $variant->updatePrice(VariantUnitPrice::fromMoney(
            $variant->getSalePrice()->multiply(3)->getExcludingVat(), // Must be higher than sale price or else discount will not apply
            $variant->getSalePrice()->getVatPercentage(),
            false
        ), $variant->getSalePrice()->multiply(2));

        if ($state) {
            $variant->updateState($state);
        }

        $product->updateVariant($variant);

        $this->catalogContext->saveProduct($product);
    }
}
