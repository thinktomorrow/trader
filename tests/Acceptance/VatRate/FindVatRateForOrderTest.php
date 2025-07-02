<?php

namespace Tests\Acceptance\VatRate;

use Thinktomorrow\Trader\Application\VatRate\VatExemptionApplication;
use Thinktomorrow\Trader\Application\VatRate\FindVatRateForOrder;
use Thinktomorrow\Trader\Domain\Common\Email;
use Thinktomorrow\Trader\Domain\Common\Locale;
use Thinktomorrow\Trader\Domain\Common\Vat\VatPercentage;
use Thinktomorrow\Trader\Domain\Model\Country\CountryId;
use Thinktomorrow\Trader\Domain\Model\Order\Address\BillingAddress;
use Thinktomorrow\Trader\Domain\Model\Order\Exceptions\CouldNotFindOrder;
use Thinktomorrow\Trader\Domain\Model\Order\Order;
use Thinktomorrow\Trader\Domain\Model\Order\OrderId;
use Thinktomorrow\Trader\Domain\Model\Order\OrderReference;
use Thinktomorrow\Trader\Domain\Model\Order\Shopper;
use Thinktomorrow\Trader\Domain\Model\Order\ShopperId;
use Thinktomorrow\Trader\Domain\Model\Order\State\DefaultOrderState;
use Thinktomorrow\Trader\Domain\Model\VatRate\BaseRate;
use Thinktomorrow\Trader\Domain\Model\VatRate\VatRate;
use Thinktomorrow\Trader\Infrastructure\Test\TestTraderConfig;

class FindVatRateForOrderTest extends VatRateContext
{
    private FindVatRateForOrder $findVatRateForOrder;

    protected function setUp(): void
    {
        parent::setUp();

        $this->findVatRateForOrder = new FindVatRateForOrder(new TestTraderConfig(), new VatExemptionApplication(new TestTraderConfig()), $this->vatRateRepository);
    }

    public function tearDown(): void
    {
        $this->orderRepository->clear();
        $this->findVatRateForOrder = new FindVatRateForOrder(new TestTraderConfig(), new VatExemptionApplication(new TestTraderConfig()), $this->vatRateRepository);

        parent::tearDown();
    }

    public function test_it_does_not_affect_shipping_cost_vat_when_no_country_rate_applies()
    {
        $this->createVatRate('NL', '10');

        $order = $this->getOrder('BE');

        $result = $this->findVatRateForOrder->findForShippingCost($order);

        $this->assertEquals('21', $result);
    }

    public function test_it_affects_shipping_cost_vat_when_country_rate_applies()
    {
        $vatRate = $this->createVatRate('NL', '10');

        $order = $this->getOrder('NL');

        $result = $this->findVatRateForOrder->findForShippingCost($order);

        $this->assertEquals($vatRate->getRate(), $result);
    }

    public function test_it_does_not_affect_payment_cost_vat_when_no_country_rate_applies()
    {
        $this->createVatRate('NL', '10');

        $order = $this->getOrder();

        $result = $this->findVatRateForOrder->findForPaymentCost($order);

        $this->assertEquals('21', $result);
    }

    public function test_it_affects_payment_cost_vat_when_country_rate_applies()
    {
        $vatRate = $this->createVatRate('NL', '10');

        $order = $this->getOrder('NL');

        $result = $this->findVatRateForOrder->findForPaymentCost($order);

        $this->assertEquals($vatRate->getRate(), $result);
    }

    public function test_it_does_not_affect_line_vat_when_no_standard_rate_applies()
    {
        $this->createVatRate('NL', '10');

        $order = $this->getOrder('BE');
        $variantVatPercentage = VatPercentage::fromString('21');

        $result = $this->findVatRateForOrder->findForLine($order, $variantVatPercentage);

        $this->assertEquals($variantVatPercentage, $result);
    }

    public function test_it_should_not_affect_line_vat_when_primary_country_rate_applies()
    {
        $this->createVatRate('BE', '10');

        $order = $this->getOrder('BE');
        $variantVatPercentage = VatPercentage::fromString('20');

        $result = $this->findVatRateForOrder->findForLine($order, $variantVatPercentage);

        $this->assertEquals($variantVatPercentage, $result);
    }

    public function test_it_should_not_affect_line_vat_billing_country_has_no_rates()
    {
        $this->createVatRate('NL', '10');

        $order = $this->getOrder('FR');
        $variantVatPercentage = VatPercentage::fromString('20');

        $result = $this->findVatRateForOrder->findForLine($order, $variantVatPercentage);

        $this->assertEquals($variantVatPercentage, $result);
    }

    public function test_it_affects_line_vat_when_country_standard_rate_applies()
    {
        $vatRate = $this->createVatRate('NL', '10');
        $vatRate2 = $this->createVatRate('NL', '15', false);
        $vatRateBE = $this->createVatRate('BE', '30');
        $this->addBaseRate($vatRateBE, $vatRate2);

        $order = $this->getOrder('NL');
        $variantVatPercentage = VatPercentage::fromString('21');

        $result = $this->findVatRateForOrder->findForLine($order, $variantVatPercentage);

        $this->assertEquals($vatRate->getRate(), $result);
    }

    public function test_it_affects_line_vat_when_country_mapped_rate_applies()
    {
        $vatRate = $this->createVatRate('NL', '10');
        $vatRate2 = $this->createVatRate('NL', '15');
        $vatRateBE = $this->createVatRate('BE', '21');
        $this->addBaseRate($vatRateBE, $vatRate2);

        $order = $this->getOrder('NL');
        $variantVatPercentage = VatPercentage::fromString('21');

        $result = $this->findVatRateForOrder->findForLine($order, $variantVatPercentage);

        $this->assertEquals($vatRate2->getRate(), $result);
    }

    public function test_it_should_return_zero_vat_when_vat_exemption_applies()
    {
        $vatRate = $this->createVatRate('BE', '10');
        $variantVatPercentage = VatPercentage::fromString('21');

        $order = $this->getOrder('NL');
        $shopper = Shopper::create(ShopperId::fromString('xxxx'), Email::fromString('foo@bar.com'), true, Locale::fromString('nl'));
        $shopper->addData([
            'vat_number' => 'BE1234567890',
            'vat_number_valid' => true,
            'vat_number_state' => 'valid',
            'vat_number_country' => 'NL',
        ]);
        $order->updateShopper($shopper);

        $result = $this->findVatRateForOrder->findForLine($order, $variantVatPercentage);
        $resultShippingCost = $this->findVatRateForOrder->findForShippingCost($order);
        $resultPaymentCost = $this->findVatRateForOrder->findForPaymentCost($order);

        $this->assertEquals(VatPercentage::zero(), $result);
        $this->assertEquals(VatPercentage::zero(), $resultShippingCost);
        $this->assertEquals(VatPercentage::zero(), $resultPaymentCost);
    }

    private function getOrder(string $billingCountryId = 'BE'): Order
    {
        $order = Order::create(
            OrderId::fromString('xxx'),
            OrderReference::fromString('xx-ref'),
            DefaultOrderState::cart_pending,
        );

        $order->updateBillingAddress(BillingAddress::fromMappedData([
            'country_id' => $billingCountryId,
            'line_1' => 'street 123',
            'line_2' => 'bus 456',
            'postal_code' => '2200',
            'city' => 'Herentals',
            'data' => json_encode([]),
        ], ['order_id' => 'xxx']));

        return $order;

        // Create an order if not already
        try {
            return $this->orderRepository->find(OrderId::fromString('xxx'));
        } catch (CouldNotFindOrder $e) {

            $order = Order::create(
                OrderId::fromString('xxx'),
                OrderReference::fromString('xx-ref'),
                DefaultOrderState::cart_pending,
            );

            $order->updateBillingAddress(BillingAddress::fromMappedData([
                'country_id' => $billingCountryId,
                'line_1' => 'street 123',
                'line_2' => 'bus 456',
                'postal_code' => '2200',
                'city' => 'Herentals',
                'data' => json_encode([]),
            ], ['order_id' => 'xxx']));

            $this->orderRepository->save($order);

            return $order;
        }
    }

    private function createVatRate(string $countryId, string $vatPercentage, bool $isStandard = true): VatRate
    {
        $vatRate = VatRate::create(
            $this->vatRateRepository->nextReference(),
            CountryId::fromString($countryId),
            VatPercentage::fromString($vatPercentage),
            $isStandard
        );

        $this->vatRateRepository->save($vatRate);

        return $vatRate;
    }

    private function addBaseRate(VatRate $originVatRate, VatRate $targetVatRate): void
    {
        $baseRate = BaseRate::create(
            $this->vatRateRepository->nextBaseRateReference(),
            $originVatRate->vatRateId,
            $targetVatRate->vatRateId,
            $originVatRate->getRate()
        );

        $targetVatRate->addBaseRate($baseRate);

        $this->vatRateRepository->save($targetVatRate);
    }
}
