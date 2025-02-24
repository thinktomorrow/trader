<?php
declare(strict_types=1);

namespace Tests\Unit\Model\Order;

use Money\Money;
use Tests\Unit\TestCase;
use Thinktomorrow\Trader\Domain\Model\Order\Discount\Discount;
use Thinktomorrow\Trader\Domain\Model\Order\Discount\DiscountTotal;
use Thinktomorrow\Trader\Domain\Model\Order\Events\PaymentAdded;
use Thinktomorrow\Trader\Domain\Model\Order\Events\PaymentDeleted;
use Thinktomorrow\Trader\Domain\Model\Order\Events\PaymentUpdated;
use Thinktomorrow\Trader\Domain\Model\Order\Exceptions\CouldNotFindPaymentOnOrder;
use Thinktomorrow\Trader\Domain\Model\Order\Exceptions\PaymentAlreadyOnOrder;
use Thinktomorrow\Trader\Domain\Model\Order\OrderId;
use Thinktomorrow\Trader\Domain\Model\Order\Payment\DefaultPaymentState;
use Thinktomorrow\Trader\Domain\Model\Order\Payment\Payment;
use Thinktomorrow\Trader\Domain\Model\Order\Payment\PaymentCost;
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
            $cost = PaymentCost::fromScalars('150', '10', true),
        );

        $this->assertEquals([
            'order_id' => 'aaa',
            'payment_id' => $paymentId->get(),
            'payment_method_id' => $paymentMethodId->get(),
            'payment_state' => $state->value,
            'cost' => $cost->getMoney()->getAmount(),
            'tax_rate' => $cost->getVatPercentage()->toPercentage()->get(),
            'includes_vat' => $cost->includesVat(),
            'data' => json_encode(['payment_method_id' => $paymentMethodId->get()]),
        ], $payment->getMappedData());
    }

    public function test_it_can_be_build_from_raw_data()
    {
        $payment = $this->createOrderPayment();

        $this->assertEquals(PaymentId::fromString('ppppp'), $payment->paymentId);
        $this->assertEquals([
            'order_id' => 'xxx',
            'payment_id' => 'ppppp',
            'payment_method_id' => 'mmm',
            'payment_state' => DefaultPaymentState::initialized->value,
            'cost' => '20',
            'tax_rate' => '10',
            'includes_vat' => true,
            'data' => json_encode(['payment_method_id' => 'mmm']),
        ], $payment->getMappedData());
    }

    public function test_it_can_find_a_payment()
    {
        $order = $this->createDefaultOrder();

        $payment = $order->findPayment($order->getPayments()[0]->paymentId);

        $this->assertEquals($payment, $order->getPayments()[0]);
    }

    public function test_it_fails_when_payment_cannot_be_found()
    {
        $this->expectException(CouldNotFindPaymentOnOrder::class);

        $order = $this->createDefaultOrder();
        $order->findPayment(PaymentId::fromString('unknown'));
    }

    public function test_it_fails_when_payment_cannot_be_found_for_update()
    {
        $this->expectException(CouldNotFindPaymentOnOrder::class);

        $order = $this->createDefaultOrder();
        $order->updatePayment($this->createOrderPayment(['payment_id' => 'unknown']));
    }

    public function test_it_can_add_a_payment()
    {
        $order = $this->createDefaultOrder();

        $order->addPayment($addedPayment = $this->createOrderPayment(['payment_id' => 'hhhh']));

        $this->assertCount(2, $order->getPayments());

        $this->assertEquals([
            new PaymentAdded($order->orderId, $addedPayment->paymentId),
        ], $order->releaseEvents());
    }

    public function test_it_cannot_add_same_payment()
    {
        $this->expectException(PaymentAlreadyOnOrder::class);

        $order = $this->createDefaultOrder();
        $order->addPayment($this->createOrderPayment());
    }

    public function test_it_can_update_payment()
    {
        $order = $this->createDefaultOrder();

        $payment = $order->getPayments()[0];
        $payment->updateCost($cost = PaymentCost::fromScalars('23', '1', false));

        $order->updatePayment($payment);

        $this->assertCount(1, $order->getPayments());
        $this->assertEquals($cost, $order->getPayments()[0]->getPaymentCost());

        $this->assertEquals([
            new PaymentUpdated($order->orderId, $payment->paymentId),
        ], $order->releaseEvents());
    }

    public function test_it_can_delete_a_payment()
    {
        $order = $this->createDefaultOrder();
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
        $payment = $this->createOrderPayment();

        $payment->updatePaymentMethod($paymentMethodId = PaymentMethodId::fromString('eee'));
        $this->assertEquals($paymentMethodId, $payment->getPaymentMethodId());
    }

    public function test_it_can_add_a_discount_to_payment()
    {
        $order = $this->createDefaultOrder();
        $payment = $order->getPayments()[0];
        $payment->addDiscount($this->createOrderPaymentDiscount(['promo_discount_id' => 'qqq', 'discount_id' => 'defgh'], $order->getMappedData()));

        $this->assertCount(1, $payment->getDiscounts());

        $paymentCost = $payment->getPaymentCost();
        $this->assertEquals(PaymentCost::fromMoney(Money::EUR(0), $paymentCost->getVatPercentage(), $paymentCost->includesVat()), $payment->getPaymentCostTotal());
    }

    public function test_it_sets_discount_tax_the_same_as_discountable_tax()
    {
        $order = $this->createDefaultOrder();
        $payment = $order->getPayments()[0];
        $payment->addDiscount($this->createOrderPaymentDiscount(['promo_discount_id' => 'qqq', 'discount_id' => 'defgh'], $order->getMappedData()));

        // 20 (and not 30 discount) because payment cost is only 20.
        $discountTotal = DiscountTotal::fromMoney(Money::EUR('20'), $payment->getPaymentCost()->getVatPercentage(), $payment->getPaymentCost()->includesVat());

        $this->assertEquals($discountTotal, $payment->getDiscountTotal());
    }
}
