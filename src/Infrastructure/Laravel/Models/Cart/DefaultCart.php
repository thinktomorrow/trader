<?php
declare(strict_types=1);

namespace Thinktomorrow\Trader\Infrastructure\Laravel\Models\Cart;

use Money\Money;
use Thinktomorrow\Trader\Application\Cart\Read\Cart;
use Thinktomorrow\Trader\Application\Cart\Read\CartBillingAddress;
use Thinktomorrow\Trader\Application\Cart\Read\CartLine;
use Thinktomorrow\Trader\Application\Cart\Read\CartPayment;
use Thinktomorrow\Trader\Application\Cart\Read\CartShipping;
use Thinktomorrow\Trader\Application\Cart\Read\CartShippingAddress;
use Thinktomorrow\Trader\Application\Cart\Read\CartShopper;
use Thinktomorrow\Trader\Application\Common\RendersData;
use Thinktomorrow\Trader\Application\Common\RendersMoney;
use Thinktomorrow\Trader\Domain\Common\Cash\Price;
use Thinktomorrow\Trader\Domain\Common\Cash\PriceTotal;

class DefaultCart implements Cart
{
    use RendersData;
    use RendersMoney;

    protected string $orderId;
    protected iterable $lines;
    protected ?CartShippingAddress $shippingAddress;
    protected ?CartBillingAddress $billingAddress;
    protected ?CartShipping $shipping;
    protected ?CartPayment $payment;
    protected ?CartShopper $shopper;
    protected iterable $discounts;
    protected array $data;

    protected PriceTotal $total;
    protected Money $taxTotal;
    protected PriceTotal $subtotal;
    protected Price $discountTotal;
    protected Price $shippingCost;
    protected Price $paymentCost;

    // General flag for all line prices to render with or without tax.
    protected bool $include_tax = true;


    public static function fromMappedData(array $state, array $childObjects, iterable $discounts): static
    {
        $cart = new static();

        $cart->orderId = $state['order_id'];

        $cart->total = $state['total'];
        $cart->taxTotal = $state['taxTotal'];
        $cart->subtotal = $state['subtotal'];
        $cart->discountTotal = $state['discountTotal'];
        $cart->shippingCost = $state['shippingCost'];
        $cart->paymentCost = $state['paymentCost'];

        $cart->lines = $childObjects[CartLine::class];
        $cart->shippingAddress = $childObjects[CartShippingAddress::class];
        $cart->billingAddress = $childObjects[CartBillingAddress::class];
        $cart->shipping = $childObjects[CartShipping::class];
        $cart->payment = $childObjects[CartPayment::class];
        $cart->shopper = $childObjects[CartShopper::class];

        $cart->data = json_decode($state['data'], true);
        $cart->discounts = $discounts;

        return $cart;
    }

    public function getOrderId(): string
    {
        return $this->orderId;
    }

    public function getLines(): iterable
    {
        return $this->lines;
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

    public function getShopper(): CartShopper
    {
        return $this->shopper;
    }

    public function getShipping(): ?CartShipping
    {
        return $this->shipping;
    }

    public function getPayment(): ?CartPayment
    {
        return $this->payment;
    }

    public function getShippingAddress(): ?CartShippingAddress
    {
        return $this->shippingAddress;
    }

    public function getBillingAddress(): ?CartBillingAddress
    {
        return $this->billingAddress;
    }

    public function getDiscounts(): array
    {
        // TODO: Implement getDiscounts() method.
    }
}
