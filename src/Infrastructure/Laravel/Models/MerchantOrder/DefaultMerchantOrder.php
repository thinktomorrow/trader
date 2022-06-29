<?php
declare(strict_types=1);

namespace Thinktomorrow\Trader\Infrastructure\Laravel\Models\MerchantOrder;

use Money\Money;
use Thinktomorrow\Trader\Application\Common\RendersData;
use Thinktomorrow\Trader\Application\Common\RendersMoney;
use Thinktomorrow\Trader\Application\Order\MerchantOrder\MerchantOrder;
use Thinktomorrow\Trader\Application\Order\MerchantOrder\MerchantOrderBillingAddress;
use Thinktomorrow\Trader\Application\Order\MerchantOrder\MerchantOrderLine;
use Thinktomorrow\Trader\Application\Order\MerchantOrder\MerchantOrderPayment;
use Thinktomorrow\Trader\Application\Order\MerchantOrder\MerchantOrderShipping;
use Thinktomorrow\Trader\Application\Order\MerchantOrder\MerchantOrderShippingAddress;
use Thinktomorrow\Trader\Application\Order\MerchantOrder\MerchantOrderShopper;
use Thinktomorrow\Trader\Domain\Common\Cash\Price;
use Thinktomorrow\Trader\Domain\Common\Cash\PriceTotal;

class DefaultMerchantOrder implements MerchantOrder
{
    use RendersData;
    use RendersMoney;

    protected string $orderId;
    protected iterable $lines;
    protected ?MerchantOrderShippingAddress $shippingAddress;
    protected ?MerchantOrderBillingAddress $billingAddress;
    protected ?MerchantOrderShipping $shipping;
    protected ?MerchantOrderPayment $payment;
    protected ?MerchantOrderShopper $shopper;
    protected array $discounts;
    protected array $data;

    protected PriceTotal $total;
    protected Money $taxTotal;
    protected PriceTotal $subtotal;
    protected Price $discountTotal;
    protected Price $shippingCost;
    protected Price $paymentCost;

    // General flag for all line prices to render with or without tax.
    protected bool $include_tax = true;


    public static function fromMappedData(array $state, array $childObjects, array $discounts): static
    {
        $order = new static();

        $order->orderId = $state['order_id'];

        $order->total = $state['total'];
        $order->taxTotal = $state['taxTotal'];
        $order->subtotal = $state['subtotal'];
        $order->discountTotal = $state['discountTotal'];
        $order->shippingCost = $state['shippingCost'];
        $order->paymentCost = $state['paymentCost'];

        $order->lines = $childObjects[MerchantOrderLine::class];
        $order->shippingAddress = $childObjects[MerchantOrderShippingAddress::class];
        $order->billingAddress = $childObjects[MerchantOrderBillingAddress::class];
        $order->shipping = $childObjects[MerchantOrderShipping::class];
        $order->payment = $childObjects[MerchantOrderPayment::class];
        $order->shopper = $childObjects[MerchantOrderShopper::class];

        $order->data = json_decode($state['data'], true);
        $order->discounts = $discounts;

        return $order;
    }

    public function getOrderId(): string
    {
        return $this->orderId;
    }

    public function getLines(): iterable
    {
        return $this->lines;
    }

    public function isEmpty(): bool
    {
        return $this->getSize() < 1;
    }

    public function getSize(): int
    {
        return count($this->getLines());
    }

    public function getQuantity(): int
    {
        return array_reduce((array) $this->getLines(), fn ($carry, $line) => $carry + $line->getQuantity(), 0);
    }

    public function includeTax(bool $includeTax = true): void
    {
        $this->include_tax = $includeTax;
    }

    public function getTotalPrice(): string
    {
        return $this->renderMoney(
            $this->include_tax ? $this->total->getIncludingVat() : $this->total->getExcludingVat(),
            $this->getLocale()
        );
    }

    public function getSubtotalPrice(): string
    {
        return $this->renderMoney(
            $this->include_tax ? $this->subtotal->getIncludingVat() : $this->subtotal->getExcludingVat(),
            $this->getLocale()
        );
    }

    public function getShippingCost(): ?string
    {
        if (! $this->shippingCost->getMoney()->isPositive()) {
            return null;
        }

        return $this->renderMoney(
            $this->include_tax ? $this->shippingCost->getIncludingVat() : $this->shippingCost->getExcludingVat(),
            $this->getLocale()
        );
    }

    public function getPaymentCost(): ?string
    {
        if (! $this->paymentCost->getMoney()->isPositive()) {
            return null;
        }

        return $this->renderMoney(
            $this->include_tax ? $this->paymentCost->getIncludingVat() : $this->paymentCost->getExcludingVat(),
            $this->getLocale()
        );
    }

    public function getDiscountPrice(): ?string
    {
        if (! $this->discountTotal->getMoney()->isPositive()) {
            return null;
        }

        return $this->renderMoney(
            $this->include_tax ? $this->discountTotal->getIncludingVat() : $this->discountTotal->getExcludingVat(),
            $this->getLocale()
        );
    }

    public function getTaxPrice(): string
    {
        return $this->renderMoney(
            $this->taxTotal,
            $this->getLocale()
        );
    }

    public function getShopper(): MerchantOrderShopper
    {
        return $this->shopper;
    }

    public function getShipping(): ?MerchantOrderShipping
    {
        return $this->shipping;
    }

    public function getPayment(): ?MerchantOrderPayment
    {
        return $this->payment;
    }

    public function getShippingAddress(): ?MerchantOrderShippingAddress
    {
        return $this->shippingAddress;
    }

    public function getBillingAddress(): ?MerchantOrderBillingAddress
    {
        return $this->billingAddress;
    }

    public function getDiscounts(): array
    {
        return $this->discounts;
    }

    public function getEnteredCoupon(): ?string
    {
        return $this->data('coupon_code');
    }

    public function getAllDiscounts(): iterable
    {
        $allDiscounts = $this->getDiscounts();

        if ($this->getShipping()) {
            $allDiscounts = array_merge($allDiscounts, $this->getShipping()->getDiscounts());
        }

        if ($this->getPayment()) {
            $allDiscounts = array_merge($allDiscounts, $this->getPayment()->getDiscounts());
        }

        foreach ($this->getLines() as $line) {
            $allDiscounts = array_merge($allDiscounts, $line->getDiscounts());
        }

        // TODO:: make sure they are unique...
        return $allDiscounts;
    }
}
