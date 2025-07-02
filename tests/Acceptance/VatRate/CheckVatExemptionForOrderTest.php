<?php

namespace Tests\Acceptance\VatRate;

use Thinktomorrow\Trader\Application\VatRate\VatExemptionApplication;
use Thinktomorrow\Trader\Domain\Common\Email;
use Thinktomorrow\Trader\Domain\Common\Locale;
use Thinktomorrow\Trader\Domain\Model\Order\Address\BillingAddress;
use Thinktomorrow\Trader\Domain\Model\Order\Order;
use Thinktomorrow\Trader\Domain\Model\Order\OrderId;
use Thinktomorrow\Trader\Domain\Model\Order\OrderReference;
use Thinktomorrow\Trader\Domain\Model\Order\Shopper;
use Thinktomorrow\Trader\Domain\Model\Order\ShopperId;
use Thinktomorrow\Trader\Domain\Model\Order\State\DefaultOrderState;
use Thinktomorrow\Trader\Infrastructure\Test\TestTraderConfig;
use Thinktomorrow\Trader\TraderConfig;

class CheckVatExemptionForOrderTest extends VatRateContext
{
    private TraderConfig $config;
    private VatExemptionApplication $checker;

    protected function setUp(): void
    {
        parent::setUp();

        $this->config = new TestTraderConfig();
        $this->checker = new VatExemptionApplication($this->config);
    }

    public function test_it_returns_false_if_vat_exemption_not_allowed()
    {
        $this->config = new TestTraderConfig(['allow_vat_exemption' => false]);
        $this->checker = new VatExemptionApplication($this->config);

        $order = $this->getOrder();

        $this->assertFalse($this->config->isVatExemptionAllowed());
        $this->assertNull($order->getShopper());

        $this->assertFalse($this->checker->verifyForOrder($order));
    }

    public function test_it_returns_false_if_shopper_is_null()
    {
        $order = $this->getOrder();

        $this->assertTrue($this->config->isVatExemptionAllowed());
        $this->assertNull($order->getShopper());

        $this->assertFalse($this->checker->verifyForOrder($order));
    }

    public function test_it_returns_false_if_shopper_is_not_business()
    {
        $order = $this->getOrder('NL');
        $order->updateShopper(Shopper::create(ShopperId::fromString('xxxx'), Email::fromString('foo@bar.com'), false, Locale::fromString('nl')));

        $this->assertTrue($this->config->isVatExemptionAllowed());
        $this->assertNotNull($order->getShopper());
        $this->assertFalse($order->getShopper()->isBusiness());

        $this->assertFalse($this->checker->verifyForOrder($order));
    }

    public function test_it_returns_false_if_vat_number_is_invalid()
    {
        $order = $this->getOrder('NL');
        $shopper = Shopper::create(ShopperId::fromString('xxxx'), Email::fromString('foo@bar.com'), true, Locale::fromString('nl'));
        $shopper->addData([
            'vat_number' => 'BE1234567890',
            'vat_number_valid' => false,
            'vat_number_state' => 'invalid',
            'vat_number_country' => 'NL',
        ]);
        $order->updateShopper($shopper);

        $this->assertTrue($this->config->isVatExemptionAllowed());
        $this->assertNotNull($order->getShopper());
        $this->assertTrue($order->getShopper()->isBusiness());
        $this->assertFalse($order->getShopper()->isVatNumberValid());

        $this->assertFalse($this->checker->verifyForOrder($order));
    }

    public function test_it_returns_false_if_vat_number_country_mismatches_billing_country()
    {
        $order = $this->getOrder('DE');
        $shopper = Shopper::create(ShopperId::fromString('xxxx'), Email::fromString('foo@bar.com'), true, Locale::fromString('nl'));
        $shopper->addData([
            'vat_number' => 'BE1234567890',
            'vat_number_valid' => true,
            'vat_number_state' => 'valid',
            'vat_number_country' => 'NL',
        ]);
        $order->updateShopper($shopper);

        $this->assertTrue($this->config->isVatExemptionAllowed());
        $this->assertNotNull($order->getShopper());
        $this->assertTrue($order->getShopper()->isBusiness());
        $this->assertTrue($order->getShopper()->isVatNumberValid());
        $this->assertNotEquals($order->getShopper()->getVatNumberCountry(), $order->getBillingAddress()->getAddress()->countryId->get());

        $this->assertFalse($this->checker->verifyForOrder($order));
    }

    public function test_it_returns_false_if_shopper_country_matches_merchant()
    {
        $order = $this->getOrder('BE');
        $shopper = Shopper::create(ShopperId::fromString('xxxx'), Email::fromString('foo@bar.com'), true, Locale::fromString('nl'));
        $shopper->addData([
            'vat_number' => 'BE1234567890',
            'vat_number_valid' => true,
            'vat_number_state' => 'valid',
            'vat_number_country' => 'BE',
        ]);
        $order->updateShopper($shopper);

        $this->assertTrue($this->config->isVatExemptionAllowed());
        $this->assertNotNull($order->getShopper());
        $this->assertTrue($order->getShopper()->isBusiness());
        $this->assertTrue($order->getShopper()->isVatNumberValid());
        $this->assertEquals('BE', $order->getShopper()->getVatNumberCountry());

        $this->assertFalse($this->checker->verifyForOrder($order));
    }

    public function test_it_returns_true_when_all_conditions_are_met()
    {
        $order = $this->getOrder('NL');
        $shopper = Shopper::create(ShopperId::fromString('xxxx'), Email::fromString('foo@bar.com'), true, Locale::fromString('nl'));
        $shopper->addData([
            'vat_number' => 'BE1234567890',
            'vat_number_valid' => true,
            'vat_number_state' => 'valid',
            'vat_number_country' => 'NL',
        ]);
        $order->updateShopper($shopper);

        $this->assertTrue($this->config->isVatExemptionAllowed());
        $this->assertNotNull($order->getShopper());
        $this->assertTrue($order->getShopper()->isBusiness());
        $this->assertTrue($order->getShopper()->isVatNumberValid());
        $this->assertEquals('NL', $order->getShopper()->getVatNumberCountry());
        $this->assertEquals($order->getShopper()->getVatNumberCountry(), $order->getBillingAddress()->getAddress()->countryId->get());

        $this->assertTrue($this->checker->verifyForOrder($order));
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
    }
}
