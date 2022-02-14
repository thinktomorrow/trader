<?php
declare(strict_types=1);

namespace Thinktomorrow\Trader\Domain\Model\Order\Details;

use Thinktomorrow\Trader\Domain\Common\Cash\Price;
use Thinktomorrow\Trader\Domain\Model\Order\OrderId;
use Thinktomorrow\Trader\Domain\Model\Order\Price\Total;
use Thinktomorrow\Trader\Domain\Model\Order\Price\SubTotal;
use Thinktomorrow\Trader\Domain\Model\Payment\PaymentTotal;
use Thinktomorrow\Trader\Domain\Model\Payment\PaymentState;
use Thinktomorrow\Trader\Domain\Model\Discount\DiscountTotal;
use Thinktomorrow\Trader\Domain\Model\Shipping\ShippingTotal;
use Thinktomorrow\Trader\Domain\Model\Payment\BillingAddress;
use Thinktomorrow\Trader\Domain\Model\Shipping\ShippingState;
use Thinktomorrow\Trader\Domain\Model\Shipping\ShippingAddress;

final class OrderDetails
{
    private array $lines;
    private array $discounts;
    private ShippingAddress $shippingAddress;
    private BillingAddress $billingAddress;
    private Shipping $shipping;
    private Payment $payment;

    private function __construct(){}

    public function getSubTotal(): SubTotal
    {
        // TODO: should cart exist without lines?
        if(count($this->lines) < 1) {
            return SubTotal::fromScalars(0, 'EUR', '0', true);
        }

        $price = array_reduce($this->lines, function(?Price $carry, Line $line){
            return $carry === null
                ? $line->getTotal()
                : $carry->add($line->getTotal());
        }, null);

        return SubTotal::fromPrice($price);
    }

    public function getTotal(): Total
    {
        return Total::fromPrice($this->getSubTotal())
            ->subtract($this->getDiscountTotal())
            ->add($this->getShippingTotal())
            ->add($this->getPaymentTotal());
    }

    public function getDiscountTotal(): DiscountTotal
    {
        if(count($this->discounts) < 1) {
            return DiscountTotal::fromScalars(0, 'EUR', '0', true);
        }

        return array_reduce($this->discounts, function(?Price $carry, Discount $discount){
            return $carry === null
                ? $discount->getTotalPrice()
                : $carry->add($discount->getTotalPrice());
        }, null);
    }

    public function getShippingAddress(): ShippingAddress
    {
        return $this->shippingAddress;
    }

    public function getBillingAddress(): BillingAddress
    {
        return $this->billingAddress;
    }

    public function getShippingTotal(): ShippingTotal
    {
        return $this->shipping->shippingTotal;
    }

    public function getShippingState(): ShippingState
    {
        return $this->shipping->shippingState;
    }

    public function getPaymentTotal(): PaymentTotal
    {
        return $this->payment->paymentTotal;
    }

    public function getPaymentState(): PaymentState
    {
        return $this->payment->paymentState;
    }

    public static function fromMappedData(array $state, array $childEntities = []): static
    {
        $orderDetails = new static();

        $orderDetails->orderId = OrderId::fromString($state['order_id']);

        $orderDetails->lines = array_map(fn($lineState) => Line::fromMappedData($lineState, $state), $childEntities[Line::class]);
        $orderDetails->discounts = array_map(fn($discountState) => Discount::fromMappedData($discountState, $state), $childEntities[Discount::class]);
        $orderDetails->shippingAddress = ShippingAddress::fromArray($childEntities[ShippingAddress::class]);
        $orderDetails->billingAddress = BillingAddress::fromArray($childEntities[BillingAddress::class]);
        $orderDetails->shipping = Shipping::fromMappedData($childEntities[Shipping::class], $state);
        $orderDetails->payment = Payment::fromMappedData($childEntities[Payment::class], $state);


        return $orderDetails;
    }
}
