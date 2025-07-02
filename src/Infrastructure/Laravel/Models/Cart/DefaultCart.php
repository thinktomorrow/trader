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
use Thinktomorrow\Trader\Domain\Common\Price\Price;
use Thinktomorrow\Trader\Domain\Common\Price\PriceTotal;

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

    final private function __construct()
    {
    }

    public static function fromMappedData(array $state, array $childObjects, array $discounts): static
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
        return array_reduce((array)$this->getLines(), fn ($carry, $line) => $carry + $line->getQuantity(), 0);
    }

    public function includeTax(bool $includeTax = true): void
    {
        $this->include_tax = $includeTax;
    }

    public function isVatExempt(): bool
    {
        return $this->dataAsPrimitive('is_vat_exempt') ?? false;
    }

    public function getTotalPrice(?bool $includeTax = null): string
    {
        return $this->renderMoney(
            $this->getTotalPriceAsMoney($includeTax),
            $this->getLocale()
        );
    }

    public function getTotalPriceAsMoney(?bool $includeTax = null): Money
    {
        $includeTax = $includeTax ?? $this->include_tax;

        return $includeTax ? $this->total->getIncludingVat() : $this->total->getExcludingVat();
    }

    public function getSubtotalPrice(?bool $includeTax = null): string
    {
        return $this->renderMoney(
            $this->getSubtotalPriceAsMoney($includeTax),
            $this->getLocale()
        );
    }

    public function getSubtotalPriceAsMoney(?bool $includeTax = null): Money
    {
        $includeTax = $includeTax ?? $this->include_tax;

        return $includeTax ? $this->subtotal->getIncludingVat() : $this->subtotal->getExcludingVat();
    }

    public function getShippingCost(?bool $includeTax = null): ?string
    {
        if (! $this->shippingCost->getMoney()->isPositive()) {
            return null;
        }

        return $this->renderMoney(
            $this->getShippingCostAsMoney($includeTax),
            $this->getLocale()
        );
    }

    public function getShippingCostAsMoney(?bool $includeTax = null): Money
    {
        $includeTax = $includeTax ?? $this->include_tax;

        return $includeTax ? $this->shippingCost->getIncludingVat() : $this->shippingCost->getExcludingVat();
    }

    public function getPaymentCost(?bool $includeTax = null): ?string
    {
        if (! $this->paymentCost->getMoney()->isPositive()) {
            return null;
        }

        return $this->renderMoney(
            $this->getPaymentCostAsMoney($includeTax),
            $this->getLocale()
        );
    }

    public function getPaymentCostAsMoney(?bool $includeTax = null): Money
    {
        $includeTax = $includeTax ?? $this->include_tax;

        return $includeTax ? $this->paymentCost->getIncludingVat() : $this->paymentCost->getExcludingVat();
    }

    public function getDiscountPrice(?bool $includeTax = null): ?string
    {
        if (! $this->discountTotal->getMoney()->isPositive()) {
            return null;
        }

        return $this->renderMoney(
            $this->getDiscountPriceAsMoney($includeTax),
            $this->getLocale()
        );
    }

    public function getDiscountPriceAsMoney(?bool $includeTax = null): Money
    {
        $includeTax = $includeTax ?? $this->include_tax;

        return $includeTax ? $this->discountTotal->getIncludingVat() : $this->discountTotal->getExcludingVat();
    }

    public function getTaxPrice(): string
    {
        return $this->renderMoney(
            $this->getTaxPriceAsMoney(),
            $this->getLocale()
        );
    }

    public function getTaxPriceAsMoney(): Money
    {
        return $this->taxTotal;
    }

    public function getShopper(): ?CartShopper
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
