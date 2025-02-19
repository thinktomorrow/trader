<?php
declare(strict_types=1);

namespace Tests\Acceptance\Order;

use Money\Money;
use Tests\Acceptance\Cart\CartContext;
use Thinktomorrow\Trader\Application\Order\MerchantOrder\MerchantOrder;
use Thinktomorrow\Trader\Application\Order\MerchantOrder\MerchantOrderPayment;
use Thinktomorrow\Trader\Application\Order\MerchantOrder\MerchantOrderShipping;
use Thinktomorrow\Trader\Application\Order\MerchantOrder\MerchantOrderShopper;
use Thinktomorrow\Trader\Domain\Common\Vat\VatPercentage;
use Thinktomorrow\Trader\Domain\Model\Order\Line\LinePrice;
use Thinktomorrow\Trader\Domain\Model\Order\OrderId;
use Thinktomorrow\Trader\Domain\Model\Order\Payment\DefaultPaymentState;
use Thinktomorrow\Trader\Domain\Model\Order\Shipping\DefaultShippingState;
use Thinktomorrow\Trader\Domain\Model\Order\State\DefaultOrderState;
use Thinktomorrow\Trader\Domain\Model\Product\Personalisation\PersonalisationType;
use Thinktomorrow\Trader\Domain\Model\Product\Variant\VariantUnitPrice;

class MerchantOrderTest extends CartContext
{
    public function test_as_a_merchant_i_need_to_be_able_to_see_the_totals()
    {
        $this->givenThereIsAProductWhichCostsEur('aaa', 5);
        $this->whenIAddTheVariantToTheCart('aaa-123', 2);

        $merchantOrder = $this->merchantOrderRepository->findMerchantOrder(OrderId::fromString('xxx'));

        $this->assertEquals('€ 10', $merchantOrder->getTotalPrice());
        $this->assertEquals('€ 10', $merchantOrder->getSubtotalPrice());
        $this->assertEquals('€ 1,67', $merchantOrder->getTaxPrice()); // tax is 20%
        $this->assertNull($merchantOrder->getDiscountPrice());
        $this->assertNull($merchantOrder->getShippingCost());
        $this->assertNull($merchantOrder->getPaymentCost());

        $this->assertEquals(Money::EUR(1000), $merchantOrder->getTotalPriceAsMoney());
        $this->assertEquals(Money::EUR(833), $merchantOrder->getTotalPriceAsMoney(false));
        $this->assertEquals(Money::EUR(1000), $merchantOrder->getSubtotalPriceAsMoney());
        $this->assertEquals(Money::EUR(833), $merchantOrder->getSubtotalPriceAsMoney(false));
        $this->assertEquals(Money::EUR(0), $merchantOrder->getShippingCostAsMoney());
        $this->assertEquals(Money::EUR(0), $merchantOrder->getShippingCostAsMoney(false));
        $this->assertEquals(Money::EUR(0), $merchantOrder->getPaymentCostAsMoney());
        $this->assertEquals(Money::EUR(0), $merchantOrder->getPaymentCostAsMoney(false));
        $this->assertEquals(Money::EUR(0), $merchantOrder->getDiscountPriceAsMoney());
        $this->assertEquals(Money::EUR(0), $merchantOrder->getDiscountPriceAsMoney(false));
        $this->assertEquals(Money::EUR(167), $merchantOrder->getTaxPriceAsMoney());


        $this->assertEquals(DefaultOrderState::cart_pending->value, $merchantOrder->getState());
        $this->assertEquals(1, $merchantOrder->getSize());
        $this->assertEquals(2, $merchantOrder->getQuantity());
    }

    public function test_it_can_get_totals()
    {
        $this->markTestSkipped('todo: with payment / shipping / discount values');
    }

    public function test_as_a_merchant_i_need_to_be_able_to_see_each_line_of_my_cart()
    {
        $this->orderRepository->setNextLineReference('foobar');

        $this->givenThereIsAProductWhichCostsEur('lightsaber', 5);
        $this->whenIAddTheVariantToTheCart('lightsaber-123', 2);

        $merchantOrder = $this->merchantOrderRepository->findMerchantOrder(OrderId::fromString('xxx'));

        // Line
        $this->assertInstanceOf(MerchantOrder::class, $merchantOrder);
        $this->assertCount(1, $merchantOrder->getLines());
        $line = $merchantOrder->getLines()[0];

        $this->assertStringStartsWith('foobar', $line->getLineId());
        $this->assertEquals('€ 5', $line->getLinePrice());
        $this->assertEquals('€ 10', $line->getTotalPrice());
        $this->assertEquals('€ 10', $line->getSubtotalPrice());
        $this->assertEquals('€ 1,67', $line->getTaxPrice()); // tax is 20%
        $this->assertEquals(2, $line->getQuantity());
        $this->assertCount(0, $line->getImages());
        $this->assertEquals('lightsaber variant', $line->getTitle());
        $this->assertNull($line->getDescription());
        $this->assertCount(0, $line->getDiscounts());
    }

    public function test_as_a_merchant_i_need_to_be_able_to_see_prices_with_or_without_tax()
    {
        $this->givenThereIsAProductWhichCostsEur('lightsaber', 5);
        $this->whenIAddTheVariantToTheCart('lightsaber-123', 2);

        $merchantOrder = $this->merchantOrderRepository->findMerchantOrder(OrderId::fromString('xxx'));

        $line = $merchantOrder->getLines()[0];

        $line->includeTax(false);

        $this->assertEquals('€ 8,33', $line->getUnitPrice()); // test unit price is set at 1000
        $this->assertEquals(Money::EUR(833), $line->getUnitPriceAsMoney());
        $this->assertEquals(VariantUnitPrice::fromMoney(Money::EUR(1000), VatPercentage::fromString('20'), true), $line->getUnitPriceAsPrice());

        $this->assertEquals('€ 4,17', $line->getLinePrice()); // 4,1666666
        $this->assertEquals(Money::EUR(417), $line->getLinePriceAsMoney()); // 4,1666666
        $this->assertEquals(LinePrice::fromMoney(Money::EUR(500), VatPercentage::fromString('20'), true), $line->getLinePriceAsPrice());

        $this->assertEquals('€ 8,33', $line->getTotalPrice()); // 8,333333
        $this->assertEquals('€ 8,33', $line->getSubtotalPrice());
        $this->assertEquals('€ 1,67', $line->getTaxPrice()); // tax is 20%

        $line->includeTax();
        $this->assertEquals('€ 5', $line->getLinePrice());
        $this->assertEquals('€ 10', $line->getTotalPrice());
        $this->assertEquals('€ 10', $line->getSubtotalPrice());
        $this->assertEquals('€ 1,67', $line->getTaxPrice()); // tax is 20%
    }

    public function test_as_a_merchant_i_need_to_be_able_to_see_shipping_address()
    {
        $this->givenThereIsAProductWhichCostsEur('lightsaber', 5);
        $this->whenIAddTheVariantToTheCart('lightsaber-123', 2);
        $this->whenIAddShippingAddress('BE', 'molenstraat 146', null, '3000', 'Antwerp');

        $merchantOrder = $this->merchantOrderRepository->findMerchantOrder(OrderId::fromString('xxx'));

        $this->assertEquals('BE', $merchantOrder->getShippingAddress()->getCountryId());
        $this->assertEquals('molenstraat 146', $merchantOrder->getShippingAddress()->getLine1());
        $this->assertNull($merchantOrder->getShippingAddress()->getLine2());
        $this->assertEquals('3000', $merchantOrder->getShippingAddress()->getPostalCode());
        $this->assertEquals('Antwerp', $merchantOrder->getShippingAddress()->getCity());
        $this->assertNull($merchantOrder->getShippingAddress()->getTitle());
        $this->assertNull($merchantOrder->getShippingAddress()->getDescription());
    }

    public function test_as_a_merchant_i_need_to_be_able_to_see_billing_address()
    {
        $this->givenThereIsAProductWhichCostsEur('lightsaber', 5);
        $this->whenIAddTheVariantToTheCart('lightsaber-123', 2);
        $this->whenIAddBillingAddress('BE', 'molenstraat 146', null, '3000', 'Antwerp');

        $merchantOrder = $this->merchantOrderRepository->findMerchantOrder(OrderId::fromString('xxx'));

        $this->assertEquals('BE', $merchantOrder->getBillingAddress()->getCountryId());
        $this->assertEquals('molenstraat 146', $merchantOrder->getBillingAddress()->getLine1());
        $this->assertNull($merchantOrder->getBillingAddress()->getLine2());
        $this->assertEquals('3000', $merchantOrder->getBillingAddress()->getPostalCode());
        $this->assertEquals('Antwerp', $merchantOrder->getBillingAddress()->getCity());
        $this->assertNull($merchantOrder->getBillingAddress()->getTitle());
        $this->assertNull($merchantOrder->getBillingAddress()->getDescription());
    }

    public function test_as_a_merchant_i_need_to_be_able_to_see_shipping()
    {
        $this->givenOrderHasAShippingCountry('BE');
        $this->givenShippingCostsForAPurchaseOfEur(30, 0, 1000);
        $this->givenThereIsAProductWhichCostsEur('lightsaber', 5);
        $this->whenIAddTheVariantToTheCart('lightsaber-123', 2);
        $this->whenIChooseShipping('bpost_home');

        $merchantOrder = $this->merchantOrderRepository->findMerchantOrder(OrderId::fromString('xxx'));

        $this->assertInstanceOf(MerchantOrderShipping::class, $merchantOrder->getShippings()[0]);
        $this->assertEquals('shipping-123', $merchantOrder->getShippings()[0]->getShippingId());
        $this->assertEquals('bpost_home', $merchantOrder->getShippings()[0]->getShippingProfileId());
        $this->assertEquals('€ 30', $merchantOrder->getShippings()[0]->getCostPrice());
        $this->assertEquals(DefaultShippingState::none->value, $merchantOrder->getShippings()[0]->getShippingState());
        $this->assertEquals('Bpost Home', $merchantOrder->getShippings()[0]->getTitle());
    }

    public function test_as_a_merchant_i_need_to_be_able_to_see_payment()
    {
        $this->givenPaymentMethod(30, 'bancontact');
        $this->givenThereIsAProductWhichCostsEur('lightsaber', 5);
        $this->whenIAddTheVariantToTheCart('lightsaber-123', 2);
        $this->whenIChoosePayment('bancontact');

        $merchantOrder = $this->merchantOrderRepository->findMerchantOrder(OrderId::fromString('xxx'));

        $this->assertInstanceOf(MerchantOrderPayment::class, $merchantOrder->getPayments()[0]);
        $this->assertEquals('payment-123', $merchantOrder->getPayments()[0]->getPaymentId());
        $this->assertEquals('bancontact', $merchantOrder->getPayments()[0]->getPaymentMethodId());
        $this->assertEquals(DefaultPaymentState::none->value, $merchantOrder->getPayments()[0]->getPaymentState());
        $this->assertEquals('€ 30', $merchantOrder->getPayments()[0]->getCostPrice());
    }

    public function test_as_a_merchant_i_need_to_be_able_to_see_guest_shopper_info()
    {
        $this->whenIEnterShopperDetails('foo@example.com');

        $merchantOrder = $this->merchantOrderRepository->findMerchantOrder(OrderId::fromString('xxx'));

        $this->assertInstanceOf(MerchantOrderShopper::class, $merchantOrder->getShopper());
        $this->assertEquals('foo@example.com', $merchantOrder->getShopper()->getEmail());
        $this->assertFalse($merchantOrder->getShopper()->isBusiness());
        $this->assertTrue($merchantOrder->getShopper()->isGuest());
        $this->assertFalse($merchantOrder->getShopper()->isCustomer());
    }

    public function test_as_a_merchant_i_need_to_be_able_to_see_customer_shopper_info()
    {
        $this->givenACustomerExists('foo@example.com');
        $this->whenIChooseCustomer('foo@example.com');

        $merchantOrder = $this->merchantOrderRepository->findMerchantOrder(OrderId::fromString('xxx'));

        $this->assertInstanceOf(MerchantOrderShopper::class, $merchantOrder->getShopper());
        $this->assertEquals('foo@example.com', $merchantOrder->getShopper()->getEmail());
        $this->assertFalse($merchantOrder->getShopper()->isBusiness());
        $this->assertFalse($merchantOrder->getShopper()->isGuest());
        $this->assertTrue($merchantOrder->getShopper()->isCustomer());
    }

    public function test_as_a_merchant_i_need_to_be_able_to_see_business_shopper_info()
    {
        $this->givenACustomerExists('foo@example.com', true);
        $this->whenIChooseCustomer('foo@example.com');

        $merchantOrder = $this->merchantOrderRepository->findMerchantOrder(OrderId::fromString('xxx'));

        $this->assertTrue($merchantOrder->getShopper()->isBusiness());
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
        $this->whenIAddTheVariantToTheCart('lightsaber-123', 1, ['foo' => 'bar'], ['xxx' => 'foobar']);
        $this->thenIShouldHaveProductInTheCart(1, 1);

        $merchantOrder = $this->merchantOrderRepository->findMerchantOrder(OrderId::fromString('xxx'));

        $line = $merchantOrder->getLines()[0];

        $this->assertCount(1, $line->getPersonalisations());

        $personalisation = $line->getPersonalisations()[0];
        $this->assertEquals('label nl', $personalisation->getLabel());
        $this->assertEquals('label nl', $personalisation->getLabel('nl'));
        $this->assertEquals('label en', $personalisation->getLabel('en'));
        $this->assertEquals('foobar', $personalisation->getValue());
        $this->assertEquals(PersonalisationType::TEXT, $personalisation->getType());
    }
}
