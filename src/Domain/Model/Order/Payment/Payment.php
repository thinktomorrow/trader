<?php
declare(strict_types=1);

namespace Thinktomorrow\Trader\Domain\Model\Order\Payment;

use Thinktomorrow\Trader\Domain\Common\Entity\ChildAggregate;
use Thinktomorrow\Trader\Domain\Common\Entity\HasData;
use Thinktomorrow\Trader\Domain\Model\Order\Discount\Discount;
use Thinktomorrow\Trader\Domain\Model\Order\Discount\Discountable;
use Thinktomorrow\Trader\Domain\Model\Order\Discount\DiscountableId;
use Thinktomorrow\Trader\Domain\Model\Order\Discount\DiscountableType;
use Thinktomorrow\Trader\Domain\Model\Order\Discount\DiscountTotal;
use Thinktomorrow\Trader\Domain\Model\Order\Discount\HasDiscounts;
use Thinktomorrow\Trader\Domain\Model\Order\OrderId;
use Thinktomorrow\Trader\Domain\Model\PaymentMethod\PaymentMethodId;

class Payment implements ChildAggregate, Discountable
{
    use HasData;
    use HasDiscounts;

    public readonly OrderId $orderId;
    public readonly PaymentId $paymentId;
    private ?PaymentMethodId $paymentMethodId;
    private PaymentState $paymentState;
    private PaymentCost $paymentCost;

    private function __construct()
    {
    }

    public static function create(OrderId $orderId, PaymentId $paymentId, PaymentMethodId $paymentMethodId, PaymentState $paymentState, PaymentCost $paymentCost): static
    {
        $payment = new static();

        $payment->orderId = $orderId;
        $payment->paymentId = $paymentId;
        $payment->paymentMethodId = $paymentMethodId;
        $payment->paymentState = $paymentState;
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

    public function updatePaymentMethod(PaymentMethodId $paymentMethodId): void
    {
        $this->paymentMethodId = $paymentMethodId;
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

    public function getPaymentCostTotal(): PaymentCost
    {
        return $this->paymentCost->subtract($this->getDiscountTotal());
    }

    public function getMappedData(): array
    {
        $data = $this->addDataIfNotNull(['payment_method_id' => $this->paymentMethodId?->get()]);

        return [
            'order_id' => $this->orderId->get(),
            'payment_id' => $this->paymentId->get(),
            'payment_method_id' => $this->paymentMethodId?->get(),
            'payment_state' => $this->paymentState->value,
            'cost' => $this->paymentCost->getMoney()->getAmount(),
            'tax_rate' => $this->paymentCost->getVatPercentage()->get(),
            'includes_vat' => $this->paymentCost->includesVat(),
            'data' => json_encode($data),
        ];
    }

    public function getChildEntities(): array
    {
        return [
            Discount::class => array_map(fn ($discount) => $discount->getMappedData(), $this->discounts),
        ];
    }

    public static function fromMappedData(array $state, array $aggregateState, array $childEntities = []): static
    {
        $payment = new static();

        if (! $state['payment_state'] instanceof PaymentState) {
            throw new \InvalidArgumentException('Payment state is expected to be instance of PaymentState. Instead ' . gettype($state['payment_state']) . ' is passed.');
        }

        $payment->orderId = OrderId::fromString($aggregateState['order_id']);
        $payment->paymentId = PaymentId::fromString($state['payment_id']);
        $payment->paymentMethodId = $state['payment_method_id'] ? PaymentMethodId::fromString($state['payment_method_id']) : null;
        $payment->paymentState = $state['payment_state'];
        $payment->paymentCost = PaymentCost::fromScalars(
            $state['cost'],
            $state['tax_rate'],
            $state['includes_vat']
        );
        $payment->discounts = array_map(fn ($discountState) => Discount::fromMappedData($discountState, $state), $childEntities[Discount::class]);
        $payment->data = json_decode($state['data'], true);

        return $payment;
    }

    public function getDiscountTotal(): DiscountTotal
    {
        return $this->calculateDiscountTotal($this->getPaymentCost());
    }

    public function getDiscountableId(): DiscountableId
    {
        return DiscountableId::fromString($this->paymentId->get());
    }

    public function getDiscountableType(): DiscountableType
    {
        return DiscountableType::payment;
    }
}
