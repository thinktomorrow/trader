<?php
declare(strict_types=1);

namespace Thinktomorrow\Trader\Domain\Model\Order\Payment;

use Thinktomorrow\Trader\Domain\Common\Entity\ChildAggregate;
use Thinktomorrow\Trader\Domain\Common\Entity\HasData;
use Thinktomorrow\Trader\Domain\Common\Price\DefaultServicePrice;
use Thinktomorrow\Trader\Domain\Common\Price\DiscountPrice;
use Thinktomorrow\Trader\Domain\Common\Price\ExtractPriceExcludingVat;
use Thinktomorrow\Trader\Domain\Common\Price\ServicePrice;
use Thinktomorrow\Trader\Domain\Model\Order\Discount\Discount;
use Thinktomorrow\Trader\Domain\Model\Order\Discount\DiscountableId;
use Thinktomorrow\Trader\Domain\Model\Order\Discount\DiscountableItem;
use Thinktomorrow\Trader\Domain\Model\Order\Discount\DiscountableType;
use Thinktomorrow\Trader\Domain\Model\Order\Discount\GetValidatedTotalDiscountPrice;
use Thinktomorrow\Trader\Domain\Model\Order\Discount\HasDiscounts;
use Thinktomorrow\Trader\Domain\Model\Order\OrderId;
use Thinktomorrow\Trader\Domain\Model\PaymentMethod\PaymentMethodId;

class Payment implements ChildAggregate, DiscountableItem
{
    use HasData;
    use HasDiscounts;

    public readonly OrderId $orderId;
    public readonly PaymentId $paymentId;
    private ?PaymentMethodId $paymentMethodId;
    private PaymentState $paymentState;
    private ServicePrice $paymentCost;

    private function __construct()
    {
    }

    public static function create(OrderId $orderId, PaymentId $paymentId, PaymentMethodId $paymentMethodId, PaymentState $paymentState, ServicePrice $paymentCost): static
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

    public function updateCost(ServicePrice $paymentCost): void
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

    public function getPaymentCost(): ServicePrice
    {
        return $this->paymentCost;
    }

    public function getPaymentCostTotal(): ServicePrice
    {
        return $this->paymentCost->applyDiscount($this->getTotalDiscountPrice());
    }

    public function getTotalDiscountPrice(): DiscountPrice
    {
        return GetValidatedTotalDiscountPrice::get($this->paymentCost, $this);
    }

    public function getMappedData(): array
    {
        $data = $this->addDataIfNotNull(['payment_method_id' => $this->paymentMethodId?->get()]);

        return [
            'order_id' => $this->orderId->get(),
            'payment_id' => $this->paymentId->get(),
            'payment_method_id' => $this->paymentMethodId?->get(),
            'payment_state' => $this->paymentState->getValueAsString(),
            'cost' => $this->paymentCost->getExcludingVat()->getAmount(),
            'data' => json_encode($data),

            // Payment is a service and has no vat by itself
            // Both these fields are no longer used but are kept for backward compatibility
            'tax_rate' => 0,
            'includes_vat' => false,
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

        $costExcludingVat = ExtractPriceExcludingVat::extract($state, 'cost');

        $payment->orderId = OrderId::fromString($aggregateState['order_id']);
        $payment->paymentId = PaymentId::fromString($state['payment_id']);
        $payment->paymentMethodId = $state['payment_method_id'] ? PaymentMethodId::fromString($state['payment_method_id']) : null;
        $payment->paymentState = $state['payment_state'];
        $payment->paymentCost = DefaultServicePrice::fromExcludingVat($costExcludingVat);
        $payment->discounts = array_map(fn ($discountState) => Discount::fromMappedData($discountState, $state), $childEntities[Discount::class]);
        $payment->data = json_decode($state['data'], true);

        return $payment;
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
