<?php
declare(strict_types=1);

namespace Thinktomorrow\Trader\Domain\Model\Payment;

use Thinktomorrow\Trader\Domain\Model\Order\OrderId;
use Thinktomorrow\Trader\Domain\Common\Entity\HasData;
use Thinktomorrow\Trader\Domain\Model\PaymentMethod\PaymentMethodId;

class Payment
{
    use HasData;

    public readonly OrderId $orderId;
    private PaymentMethodId $paymentMethodId;
    private PaymentState $paymentState;
    private PaymentCost $paymentCost;

    private function __construct()
    {
    }

    public static function create(OrderId $orderId, PaymentMethodId $paymentMethodId, PaymentCost $paymentCost): static
    {
        $payment = new static();

        $payment->orderId = $orderId;
        $payment->paymentMethodId = $paymentMethodId;
        $payment->paymentState = PaymentState::none;
        $payment->paymentCost = $paymentCost;

        return $payment;
    }

    public function updateState(PaymentState $paymentState): void
    {
        $this->paymentState = $paymentState;
    }

    public function updateCost(PaymentCost $paymentCost): void
    {
        $this->paymentCost = $paymentCost;
    }

    public function getPaymentMethodId(): PaymentMethodId
    {
        return $this->paymentMethodId;
    }

    public function getPaymentState(): PaymentState
    {
        return $this->paymentState;
    }

    public function getPaymentCost(): PaymentCost
    {
        return $this->paymentCost;
    }

    public function getMappedData(): array
    {
        return [
            'order_id'          => $this->orderId->get(),
            'payment_method_id' => $this->paymentMethodId->get(),
            'payment_state'     => $this->paymentState->value,
            'payment_cost'      => $this->paymentCost->getMoney()->getAmount(),
            'tax_rate'          => $this->paymentCost->getTaxRate()->toPercentage()->get(),
            'includes_vat'      => $this->paymentCost->includesTax(),
            'data'              => $this->data,
        ];
    }

    public static function make(OrderId $orderId, PaymentMethodId $paymentMethodId, PaymentState $paymentState, PaymentCost $paymentTotal, array $data): static
    {
        $payment = new static();

        $payment->orderId = $orderId;
        $payment->paymentMethodId = $paymentMethodId;
        $payment->paymentState = $paymentState;
        $payment->paymentCost = $paymentTotal;
        $payment->data = $data;

        return $payment;
    }

    public static function fromMappedData(array $state, array $aggregateState): static
    {
        $payment = new static();

        $payment->orderId = OrderId::fromString($aggregateState['order_id']);
        $payment->paymentMethodId = PaymentMethodId::fromString($state['payment_method_id']);
        $payment->paymentState = PaymentState::from($state['payment_state']);
        $payment->paymentCost = PaymentCost::fromScalars(
            $state['payment_cost'], 'EUR', $state['tax_rate'], $state['includes_vat']
        );
        $payment->data = $state['data'];

        return $payment;
    }
}
