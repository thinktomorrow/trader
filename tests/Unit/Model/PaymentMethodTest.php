<?php
declare(strict_types=1);

namespace Tests\Unit\Model;

use Money\Money;
use Tests\Unit\TestCase;
use Thinktomorrow\Trader\Domain\Model\PaymentMethod\PaymentMethod;
use Thinktomorrow\Trader\Domain\Model\PaymentMethod\PaymentMethodId;

class PaymentMethodTest extends TestCase
{
    /** @test */
    public function it_can_create_an_payment_method_entity()
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
