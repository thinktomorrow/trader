<?php

declare(strict_types=1);

namespace Tests\Unit\Model\Order;

use Money\Money;
use Tests\Unit\TestCase;
use Thinktomorrow\Trader\Domain\Common\Price\DefaultDiscountPrice;
use Thinktomorrow\Trader\Domain\Common\Price\DefaultServicePrice;
use Thinktomorrow\Trader\Domain\Model\Order\Events\PaymentAdded;
use Thinktomorrow\Trader\Domain\Model\Order\Events\PaymentDeleted;
use Thinktomorrow\Trader\Domain\Model\Order\Events\PaymentUpdated;
use Thinktomorrow\Trader\Domain\Model\Order\Exceptions\CouldNotFindPaymentOnOrder;
use Thinktomorrow\Trader\Domain\Model\Order\Exceptions\PaymentAlreadyOnOrder;
use Thinktomorrow\Trader\Domain\Model\Order\OrderId;
use Thinktomorrow\Trader\Domain\Model\Order\Payment\DefaultPaymentState;
use Thinktomorrow\Trader\Domain\Model\Order\Payment\Payment;
use Thinktomorrow\Trader\Domain\Model\Order\Payment\PaymentId;
use Thinktomorrow\Trader\Domain\Model\PaymentMethod\PaymentMethodId;

class PaymentTest extends TestCase
{
    public function test_it_can_create_a_order_payment()
    {
        $payment = Payment::create(
            OrderId::fromString('aaa'),
            $paymentId = PaymentId::fromString('yyy'),
            $paymentMethodId = PaymentMethodId::fromString('zzz'),
            $state = DefaultPaymentState::getDefaultState(),
            $cost = DefaultServicePrice::fromExcludingVat(Money::EUR(150)),
        );

        $this->assertEquals([
            'order_id' => 'aaa',
            'payment_id' => $paymentId->get(),
            'payment_method_id' => $paymentMethodId->get(),
            'payment_state' => $state->value,
            'cost_excl' => $cost->getExcludingVat()->getAmount(),
            'discount_excl' => 0,
            'total_excl' => $cost->getExcludingVat()->getAmount(),
            'data' => json_encode(['payment_method_id' => $paymentMethodId->get()]),
        ], $payment->getMappedData());
    }

    public function test_it_can_be_build_from_raw_data()
    {
        $payment = $this->orderContext->createPayment();

        $this->assertEquals(PaymentId::fromString('order-aaa:payment-aaa'), $payment->paymentId);
        $this->assertEquals([
            'order_id' => 'order-aaa',
            'payment_id' => 'order-aaa:payment-aaa',
            'payment_method_id' => 'payment-method-aaa',
            'payment_state' => DefaultPaymentState::initialized->value,
            'cost_excl' => '50',
            'discount_excl' => '0',
            'total_excl' => '50',
            'data' => json_encode([
                'title' => ['nl' => 'payment-aaa title nl', 'fr' => 'payment-aaa title fr'],
                'payment_method_id' => 'payment-method-aaa',
            ]),
        ], $payment->getMappedData());
    }

    public function test_it_can_find_a_payment()
    {
        $order = $this->orderContext->createDefaultOrder();

        $payment = $order->findPayment($order->getPayments()[0]->paymentId);

        $this->assertEquals($payment, $order->getPayments()[0]);
    }

    public function test_it_fails_when_payment_cannot_be_found()
    {
        $this->expectException(CouldNotFindPaymentOnOrder::class);

        $order = $this->orderContext->createDefaultOrder();
        $order->findPayment(PaymentId::fromString('unknown'));
    }

    public function test_it_fails_when_payment_cannot_be_found_for_update()
    {
        $this->expectException(CouldNotFindPaymentOnOrder::class);

        $order = $this->orderContext->createDefaultOrder();
        $order->updatePayment($this->orderContext->createPayment('order-aaa', 'unknown'));
    }

    public function test_it_can_add_a_payment()
    {
        $order = $this->orderContext->createDefaultOrder();

        $order->addPayment($addedPayment = $this->orderContext->createPayment('order-aaa', 'unknown'));

        $this->assertCount(2, $order->getPayments());

        $this->assertEquals([
            new PaymentAdded($order->orderId, $addedPayment->paymentId),
        ], $order->releaseEvents());
    }

    public function test_it_cannot_add_same_payment()
    {
        $this->expectException(PaymentAlreadyOnOrder::class);

        $order = $this->orderContext->createDefaultOrder();
        $order->addPayment($this->orderContext->createPayment());
    }

    public function test_it_can_update_payment()
    {
        $order = $this->orderContext->createDefaultOrder();

        $payment = $order->getPayments()[0];
        $payment->updateCost($cost = DefaultServicePrice::fromExcludingVat(Money::EUR(23)));

        $order->updatePayment($payment);

        $this->assertCount(1, $order->getPayments());
        $this->assertEquals($cost, $order->getPayments()[0]->getPaymentCost());

        $this->assertEquals([
            new PaymentUpdated($order->orderId, $payment->paymentId),
        ], $order->releaseEvents());
    }

    public function test_it_can_delete_a_payment()
    {
        $order = $this->orderContext->createDefaultOrder();
        $paymentId = $order->getPayments()[0]->paymentId;

        $this->assertCount(1, $order->getPayments());

        $order->deletePayment($paymentId);

        $this->assertCount(0, $order->getPayments());

        $this->assertEquals([
            new PaymentDeleted($order->orderId, $paymentId),
        ], $order->releaseEvents());
    }

    public function test_it_can_update_payment_method()
    {
        $payment = $this->orderContext->createPayment();

        $payment->updatePaymentMethod($paymentMethodId = PaymentMethodId::fromString('eee'));
        $this->assertEquals($paymentMethodId, $payment->getPaymentMethodId());
    }

    public function test_it_can_add_a_discount_to_payment()
    {
        $order = $this->orderContext->createDefaultOrder();
        $payment = $order->getPayments()[0];
        $payment->addDiscount($this->orderContext->createPaymentDiscount());

        $this->assertCount(1, $payment->getDiscounts());

        $this->assertEquals(DefaultServicePrice::fromExcludingVat(Money::EUR(50)), $payment->getPaymentCost());
        $this->assertEquals(DefaultDiscountPrice::fromExcludingVat(Money::EUR(15)), $payment->getDiscountPrice());
        $this->assertEquals(DefaultServicePrice::fromExcludingVat(Money::EUR(35)), $payment->getPaymentCostTotal());
    }
}
