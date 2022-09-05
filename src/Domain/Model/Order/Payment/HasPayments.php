<?php
declare(strict_types=1);

namespace Thinktomorrow\Trader\Domain\Model\Order\Payment;

use Thinktomorrow\Trader\Domain\Model\Order\Events\PaymentAdded;
use Thinktomorrow\Trader\Domain\Model\Order\Events\PaymentDeleted;
use Thinktomorrow\Trader\Domain\Model\Order\Events\PaymentUpdated;
use Thinktomorrow\Trader\Domain\Model\Order\Exceptions\CouldNotFindPaymentOnOrder;
use Thinktomorrow\Trader\Domain\Model\Order\Exceptions\PaymentAlreadyOnOrder;

trait HasPayments
{
    /** @var Payment[] */
    private array $payments = [];

    public function addPayment(Payment $payment): void
    {
        if (null !== $this->findPaymentIndex($payment->paymentId)) {
            throw new PaymentAlreadyOnOrder(
                'Cannot add payment because order ['.$this->orderId->get().'] already has payment ['.$payment->paymentId->get().']'
            );
        }

        $this->payments[] = $payment;

        $this->recordEvent(new PaymentAdded($this->orderId, $payment->paymentId));
    }

    public function updatePayment(Payment $payment): void
    {
        if (null === $paymentIndex = $this->findPaymentIndex($payment->paymentId)) {
            throw new CouldNotFindPaymentOnOrder(
                'Cannot update payment because order ['.$this->orderId->get().'] has no payment by id ['.$payment->paymentId->get().']'
            );
        }

        $this->payments[$paymentIndex] = $payment;

        $this->recordEvent(new PaymentUpdated($this->orderId, $payment->paymentId));
    }

    public function deletePayment(PaymentId $paymentId): void
    {
        if (null !== $paymentIndex = $this->findPaymentIndex($paymentId)) {
            unset($this->payments[$paymentIndex]);

            $this->recordEvent(new PaymentDeleted($this->orderId, $paymentId));
        }
    }

    public function findPayment(PaymentId $paymentId): Payment
    {
        if (null === $paymentIndex = $this->findPaymentIndex($paymentId)) {
            throw new CouldNotFindPaymentOnOrder(
                'Cannot update payment because order ['.$this->orderId->get().'] has no payment by id ['.$paymentId->get().']'
            );
        }

        return $this->payments[$paymentIndex];
    }

    private function findPaymentIndex(PaymentId $paymentId): ?int
    {
        foreach ($this->payments as $index => $payment) {
            if ($paymentId->equals($payment->paymentId)) {
                return $index;
            }
        }

        return null;
    }
}
