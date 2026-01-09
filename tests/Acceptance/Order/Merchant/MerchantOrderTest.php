<?php
declare(strict_types=1);

namespace Tests\Acceptance\Order\Merchant;

use Money\Money;
use Tests\Acceptance\Cart\CartContext;
use Thinktomorrow\Trader\Application\Cart\RefreshCart\Adjusters\AdjustOrderVatSnapshot;
use Thinktomorrow\Trader\Application\Order\MerchantOrder\MerchantOrder;
use Thinktomorrow\Trader\Application\Order\MerchantOrder\MerchantOrderPayment;
use Thinktomorrow\Trader\Application\Order\MerchantOrder\MerchantOrderShipping;
use Thinktomorrow\Trader\Application\Order\MerchantOrder\MerchantOrderShopper;
use Thinktomorrow\Trader\Domain\Model\Order\OrderId;
use Thinktomorrow\Trader\Domain\Model\Order\Payment\DefaultPaymentState;
use Thinktomorrow\Trader\Domain\Model\Order\Shipping\DefaultShippingState;
use Thinktomorrow\Trader\Domain\Model\Order\State\DefaultOrderState;
use Thinktomorrow\Trader\Domain\Model\Product\Personalisation\PersonalisationType;
use Thinktomorrow\Trader\Infrastructure\Test\TestContainer;

class MerchantOrderTest extends CartContext
{
    public function test_as_a_merchant_i_need_to_be_able_to_see_the_totals()
    {
        $order = $this->orderContext->createDefaultOrder();

        (new TestContainer())->get(AdjustOrderVatSnapshot::class)->adjust($order);
        $this->orderContext->saveOrder($order);

        $order = $this->orderContext->findMerchantOrder($order->orderId->get());

        $this->assertEquals(DefaultOrderState::cart_pending->value, $order->getState());
        $this->assertEquals(2, $order->getSize());
        $this->assertEquals(2, $order->getQuantity());

        $this->assertEquals(Money::EUR('166'), $order->getSubtotalExcl());
        $this->assertEquals(Money::EUR('200'), $order->getSubtotalIncl());
        $this->assertEquals(Money::EUR('50'), $order->getShippingCostExcl());
        $this->assertEquals(Money::EUR('61'), $order->getShippingCostIncl());
        $this->assertEquals(Money::EUR('50'), $order->getPaymentCostExcl());
        $this->assertEquals(Money::EUR('61'), $order->getPaymentCostIncl());
        $this->assertEquals(Money::EUR('15'), $order->getDiscountTotalExcl());
        $this->assertEquals(Money::EUR('18'), $order->getDiscountTotalIncl());
        $this->assertEquals(Money::EUR('251'), $order->getTotalExcl());
        $this->assertEquals(Money::EUR('53'), $order->getTotalVat());
        $this->assertEquals(Money::EUR('304'), $order->getTotalIncl());
    }

    public function test_it_can_get_formatted_totals()
    {
        $order = $this->orderContext->createDefaultOrder();

        (new TestContainer())->get(AdjustOrderVatSnapshot::class)->adjust($order);
        $this->orderContext->saveOrder($order);

        $order = $this->orderContext->findMerchantOrder($order->orderId->get());

        $this->assertEquals('€ 1,66', $order->getFormattedSubtotalExcl());
        $this->assertEquals('€ 2', $order->getFormattedSubtotalIncl());
        $this->assertEquals('€ 0,50', $order->getFormattedShippingCostExcl());
        $this->assertEquals('€ 0,61', $order->getFormattedShippingCostIncl());
        $this->assertEquals('€ 0,50', $order->getFormattedPaymentCostExcl());
        $this->assertEquals('€ 0,61', $order->getFormattedPaymentCostIncl());
        $this->assertEquals('€ 0,15', $order->getFormattedDiscountTotalExcl());
        $this->assertEquals('€ 0,18', $order->getFormattedDiscountTotalIncl());
        $this->assertEquals('€ 2,51', $order->getFormattedTotalExcl());
        $this->assertEquals('€ 0,53', $order->getFormattedTotalVat());
        $this->assertEquals('€ 3,04', $order->getFormattedTotalIncl());
    }

    public function test_as_a_merchant_i_need_to_be_able_to_see_each_line_of_the_order()
    {
        $order = $this->orderContext->createDefaultOrder();
        $order = $this->orderContext->findMerchantOrder($order->orderId->get());

        // Line
        $this->assertInstanceOf(MerchantOrder::class, $order);
        $this->assertCount(2, $order->getLines());
        $line = $order->getLines()[0];

        $this->assertEquals('order-aaa:line-aaa', $line->getLineId());
        $this->assertEquals(1, $line->getQuantity());
        $this->assertCount(0, $line->getImages());
        $this->assertEquals('line-aaa title nl', $line->getTitle());
        $this->assertNull($line->getDescription());
        $this->assertCount(0, $line->getDiscounts());

        $this->assertEquals(Money::EUR('83'), $line->getUnitPriceExcl());
        $this->assertEquals(Money::EUR('100'), $line->getUnitPriceIncl());
        $this->assertEquals(Money::EUR('83'), $line->getTotalPriceExcl());
        $this->assertEquals(Money::EUR('100'), $line->getTotalPriceIncl());
        $this->assertEquals(Money::EUR('17'), $line->getTotalVat());
        $this->assertEquals(Money::EUR('0'), $line->getDiscountPriceExcl());
        $this->assertEquals(Money::EUR('0'), $line->getDiscountPriceIncl());

        $this->assertEquals('€ 0,83', $line->getFormattedUnitPriceExcl());
        $this->assertEquals('€ 1', $line->getFormattedUnitPriceIncl());
        $this->assertEquals('€ 0,83', $line->getFormattedTotalPriceExcl());
        $this->assertEquals('€ 1', $line->getFormattedTotalPriceIncl());
        $this->assertEquals('€ 0,17', $line->getFormattedTotalVat());
        $this->assertEquals('€ 0', $line->getFormattedDiscountPriceExcl());
        $this->assertEquals('€ 0', $line->getFormattedDiscountPriceIncl());
    }

    public function test_as_a_merchant_i_need_to_be_able_to_see_shipping_address()
    {
        $order = $this->orderContext->createDefaultOrder();
        $order = $this->orderContext->findMerchantOrder($order->orderId->get());

        $this->assertEquals('BE', $order->getShippingAddress()->getCountryId());
        $this->assertEquals('Lierseweg 81', $order->getShippingAddress()->getLine1());
        $this->assertNull($order->getShippingAddress()->getLine2());
        $this->assertEquals('2200', $order->getShippingAddress()->getPostalCode());
        $this->assertEquals('Herentals', $order->getShippingAddress()->getCity());
        $this->assertNull($order->getShippingAddress()->getTitle());
        $this->assertNull($order->getShippingAddress()->getDescription());
    }

    public function test_as_a_merchant_i_need_to_be_able_to_see_billing_address()
    {
        $order = $this->orderContext->createDefaultOrder();
        $order = $this->orderContext->findMerchantOrder($order->orderId->get());

        $this->assertEquals('NL', $order->getBillingAddress()->getCountryId());
        $this->assertEquals('Example 12', $order->getBillingAddress()->getLine1());
        $this->assertNull($order->getBillingAddress()->getLine2());
        $this->assertEquals('1000', $order->getBillingAddress()->getPostalCode());
        $this->assertEquals('Amsterdam', $order->getBillingAddress()->getCity());
        $this->assertNull($order->getBillingAddress()->getTitle());
        $this->assertNull($order->getBillingAddress()->getDescription());
    }

    public function test_as_a_merchant_i_need_to_be_able_to_see_shipping()
    {
        $order = $this->orderContext->createDefaultOrder();
        $order = $this->orderContext->findMerchantOrder($order->orderId->get());

        $this->assertInstanceOf(MerchantOrderShipping::class, $order->getShippings()[0]);
        $this->assertEquals('order-aaa:shipping-aaa', $order->getShippings()[0]->getShippingId());
        $this->assertEquals('shipping-profile-aaa', $order->getShippings()[0]->getShippingProfileId());
        $this->assertEquals(DefaultShippingState::none->value, $order->getShippings()[0]->getShippingState());
        $this->assertEquals('shipping-aaa title nl', $order->getShippings()[0]->getTitle());

        $this->assertEquals(Money::EUR(50), $order->getShippings()[0]->getCostPriceExcl());
        $this->assertEquals(Money::EUR(50), $order->getShippings()[0]->getTotalPriceExcl());
        $this->assertEquals(Money::EUR(0), $order->getShippings()[0]->getDiscountPriceExcl());

        $this->assertEquals('€ 0,50', $order->getShippings()[0]->getFormattedCostPriceExcl());
        $this->assertEquals('€ 0,50', $order->getShippings()[0]->getFormattedTotalPriceExcl());
        $this->assertEquals('€ 0', $order->getShippings()[0]->getFormattedDiscountPriceExcl());
    }

    public function test_as_a_merchant_i_need_to_be_able_to_see_payment()
    {
        $order = $this->orderContext->createDefaultOrder();
        $order = $this->orderContext->findMerchantOrder($order->orderId->get());

        $this->assertInstanceOf(MerchantOrderPayment::class, $order->getPayments()[0]);
        $this->assertEquals('order-aaa:payment-aaa', $order->getPayments()[0]->getPaymentId());
        $this->assertEquals('payment-method-aaa', $order->getPayments()[0]->getPaymentMethodId());
        $this->assertEquals(DefaultPaymentState::initialized->value, $order->getPayments()[0]->getPaymentState());

        $this->assertEquals(Money::EUR(50), $order->getPayments()[0]->getCostPriceExcl());
        $this->assertEquals(Money::EUR(50), $order->getPayments()[0]->getTotalPriceExcl());
        $this->assertEquals(Money::EUR(0), $order->getPayments()[0]->getDiscountPriceExcl());

        $this->assertEquals('€ 0,50', $order->getPayments()[0]->getFormattedCostPriceExcl());
        $this->assertEquals('€ 0,50', $order->getPayments()[0]->getFormattedTotalPriceExcl());
        $this->assertEquals('€ 0', $order->getPayments()[0]->getFormattedDiscountPriceExcl());
    }

    public function test_as_a_merchant_i_need_to_be_able_to_see_guest_shopper_info()
    {
        $order = $this->orderContext->createDefaultOrder();
        $order = $this->orderContext->findMerchantOrder($order->orderId->get());

        $this->assertInstanceOf(MerchantOrderShopper::class, $order->getShopper());
        $this->assertEquals('ben@thinktomorrow.be', $order->getShopper()->getEmail());
        $this->assertFalse($order->getShopper()->isBusiness());
        $this->assertTrue($order->getShopper()->isGuest());
        $this->assertFalse($order->getShopper()->isCustomer());
    }

    public function test_as_a_merchant_i_need_to_be_able_to_see_customer_shopper_info()
    {
        $this->givenACustomerExists('ben@thinktomorrow.be');
        $this->whenIChooseCustomer('ben@thinktomorrow.be');

        // Vat Snapshot needs to be adjusted to pick up customer info
        $order = $this->orderContext->findOrder(OrderId::fromString('xxx'));
        (new TestContainer())->get(AdjustOrderVatSnapshot::class)->adjust($order);
        $this->orderContext->saveOrder($order);

        $order = $this->orderContext->findMerchantOrder(OrderId::fromString('xxx'));

        $this->assertInstanceOf(MerchantOrderShopper::class, $order->getShopper());
        $this->assertEquals('ben@thinktomorrow.be', $order->getShopper()->getEmail());
        $this->assertFalse($order->getShopper()->isBusiness());
        $this->assertFalse($order->getShopper()->isGuest());
        $this->assertTrue($order->getShopper()->isCustomer());
    }

    public function test_as_a_merchant_i_need_to_be_able_to_see_business_shopper_info()
    {
        $this->givenACustomerExists('ben@thinktomorrow.be', true);
        $this->whenIChooseCustomer('ben@thinktomorrow.be');

        // Vat Snapshot needs to be adjusted to pick up customer info
        $order = $this->orderContext->findOrder(OrderId::fromString('xxx'));
        (new TestContainer())->get(AdjustOrderVatSnapshot::class)->adjust($order);
        $this->orderContext->saveOrder($order);

        $order = $this->orderContext->findMerchantOrder(OrderId::fromString('xxx'));

        $this->assertTrue($order->getShopper()->isBusiness());
    }

    public function test_as_a_merchant_i_need_to_be_able_to_see_a_line_personalisation()
    {
        $this->givenThereIsAProductWhichCostsEur('lightsaber', 5);
        $this->givenThereIsAProductPersonalisation('lightsaber', [
            [
                'personalisation_id' => 'xxx',
                'personalisation_type' => PersonalisationType::TEXT,
                'data' => ['label' => ['nl' => 'label nl', 'en' => 'label en']],
            ],
        ]);
        $this->whenIAddTheVariantToTheCart('lightsaber-variant-aaa', 1, ['foo' => 'bar'], ['xxx' => 'foobar']);
        $this->thenIShouldHaveProductInTheCart(1, 1);

        $order = $this->orderContext->findMerchantOrder(OrderId::fromString('xxx'));

        $line = $order->getLines()[0];

        $this->assertCount(1, $line->getPersonalisations());

        $personalisation = $line->getPersonalisations()[0];
        $this->assertEquals('label nl', $personalisation->getLabel());
        $this->assertEquals('label nl', $personalisation->getLabel('nl'));
        $this->assertEquals('label en', $personalisation->getLabel('en'));
        $this->assertEquals('foobar', $personalisation->getValue());
        $this->assertEquals(PersonalisationType::TEXT, $personalisation->getType());
    }

    public function test_as_a_merchant_i_need_to_be_able_to_see_if_order_is_vat_exempt()
    {
        $this->givenThereIsAProductWhichCostsEur('lightsaber', 5);
        $this->whenIAddBillingAddress('NL', 'Lierseweg 81', null, '2200', 'Herentals');
        $this->whenIAddTheVariantToTheCart('lightsaber-variant-aaa', 2);

        // Update shopper triggers vat number and vat exemption verification
        $this->whenIEnterShopperDetails('ben@thinktomorrow.be', true);

        $order = $this->orderContext->findMerchantOrder(OrderId::fromString('xxx'));

        $this->assertTrue($order->isVatExempt());
    }

    public function test_as_a_merchant_i_need_to_be_able_to_see_if_order_is_not_vat_exempt()
    {
        $this->givenThereIsAProductWhichCostsEur('lightsaber', 5);
        $this->whenIAddTheVariantToTheCart('lightsaber-variant-aaa', 2);
        $this->whenIAddBillingAddress('BE', 'Lierseweg 81', null, '2200', 'Herentals');

        // Update shopper triggers vat number and vat exemption verification
        $this->whenIEnterShopperDetails('ben@thinktomorrow.be', true);

        $order = $this->orderContext->findMerchantOrder(OrderId::fromString('xxx'));

        $this->assertFalse($order->isVatExempt());
    }
}
