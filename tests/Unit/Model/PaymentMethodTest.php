<?php
declare(strict_types=1);

namespace Tests\Unit\Model;

use Money\Money;
use Tests\Unit\TestCase;
use Thinktomorrow\Trader\Domain\Common\Email;
use Thinktomorrow\Trader\Domain\Model\Order\Order;
use Thinktomorrow\Trader\Domain\Model\Order\OrderId;
use Thinktomorrow\Trader\Domain\Model\Order\Line\Line;
use Thinktomorrow\Trader\Domain\Model\Order\OrderState;
use Thinktomorrow\Trader\Domain\Model\Order\Line\LineId;
use Thinktomorrow\Trader\Domain\Model\Customer\CustomerId;
use Thinktomorrow\Trader\Domain\Model\Order\Line\Quantity;
use Thinktomorrow\Trader\Domain\Model\Order\OrderReference;
use Thinktomorrow\Trader\Domain\Model\Order\Line\LinePrice;
use Thinktomorrow\Trader\Domain\Model\Order\Payment\Payment;
use Thinktomorrow\Trader\Domain\Model\Order\Events\LineAdded;
use Thinktomorrow\Trader\Domain\Model\Order\Shipping\Shipping;
use Thinktomorrow\Trader\Domain\Model\Order\Payment\PaymentId;
use Thinktomorrow\Trader\Domain\Model\Order\Events\LineUpdated;
use Thinktomorrow\Trader\Domain\Model\Order\Events\LineDeleted;
use Thinktomorrow\Trader\Domain\Model\Order\Events\OrderCreated;
use Thinktomorrow\Trader\Domain\Model\Order\Events\OrderUpdated;
use Thinktomorrow\Trader\Domain\Model\Order\Shipping\ShippingId;
use Thinktomorrow\Trader\Domain\Model\Order\Payment\PaymentCost;
use Thinktomorrow\Trader\Domain\Model\Product\Variant\VariantId;
use Thinktomorrow\Trader\Domain\Model\Order\Events\ShippingAdded;
use Thinktomorrow\Trader\Domain\Model\Order\Shipping\ShippingCost;
use Thinktomorrow\Trader\Domain\Model\PaymentMethod\PaymentMethod;
use Thinktomorrow\Trader\Domain\Model\Order\Events\ShippingUpdated;
use Thinktomorrow\Trader\Domain\Model\Order\Address\BillingAddress;
use Thinktomorrow\Trader\Domain\Model\PaymentMethod\PaymentMethodId;
use Thinktomorrow\Trader\Domain\Model\Order\Address\ShippingAddress;
use Thinktomorrow\Trader\Domain\Model\ShippingProfile\ShippingProfileId;

class PaymentMethodTest extends TestCase
{
    /** @test */
    public function it_can_create_an_paymentMethod_entity()
    {
        $paymentMethod = PaymentMethod::create(
            $paymentMethodId = PaymentMethodId::fromString('xxx'),
            Money::EUR(10),
        );

        $this->assertEquals([
            'paymentmethod_id' => $paymentMethodId->get(),
            'rate' => '10',
            'data' => "[]",
        ], $paymentMethod->getMappedData());
    }

    /** @test */
    public function it_can_update_rate()
    {
        $paymentMethod = $this->createPaymentMethod();

        $paymentMethod->updateRate(Money::EUR(30));

        $this->assertEquals(Money::EUR(30), $paymentMethod->getRate());
    }

    /** @test */
    public function adding_data_merges_with_existing_data()
    {
        $paymentMethod = $this->createdOrder();

        $paymentMethod->addData(['bar' => 'baz']);
        $paymentMethod->addData(['foo' => 'bar', 'bar' => 'boo']);

        $this->assertEquals(json_encode(['bar' => 'boo', 'foo' => 'bar']), $paymentMethod->getMappedData()['data']);
    }

    /** @test */
    public function it_can_delete_data()
    {
        $paymentMethod = $this->createdOrder();

        $paymentMethod->addData(['foo' => 'bar', 'bar' => 'boo']);
        $paymentMethod->deleteData('bar');

        $this->assertEquals(json_encode(['foo' => 'bar']), $paymentMethod->getMappedData()['data']);
    }
}
