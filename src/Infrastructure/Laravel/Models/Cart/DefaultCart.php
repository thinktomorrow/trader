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
use Thinktomorrow\Trader\Domain\Model\Order\WithOrderTotals;

class DefaultCart implements Cart
{
    use RendersData;
    use RendersMoney;
    use WithOrderTotals;

    protected string $orderId;
    protected iterable $lines;
    protected ?CartShippingAddress $shippingAddress;
    protected ?CartBillingAddress $billingAddress;
    protected ?CartShipping $shipping;
    protected ?CartPayment $payment;
    protected ?CartShopper $shopper;
    protected array $discounts;
    protected array $data;

//    protected TotalPrice $total;
//    protected Money $taxTotal;
//    protected TotalPrice $subtotal;
//    protected Price $discountTotal;
//    protected Price $shippingCost;
//    protected Price $paymentCost;

    // General flag for all line prices to render with or without tax.
    protected bool $include_tax = true;

    final private function __construct()
    {
    }

    public static function fromMappedData(array $state, array $childObjects, array $discounts): static
    {
        $cart = new static();

        $cart->orderId = $state['order_id'];

        $cart->totalExcl = Money::EUR($state['total_excl']);
        $cart->totalIncl = Money::EUR($state['total_incl']);
        $cart->totalVat = Money::EUR($state['total_vat']);
        $cart->subtotalExcl = Money::EUR($state['subtotal_excl']);
        $cart->subtotalIncl = Money::EUR($state['subtotal_incl']);
        $cart->discountExcl = Money::EUR($state['discount_total_excl']);
        $cart->discountIncl = Money::EUR($state['discount_total_incl']);
        $cart->shippingExcl = Money::EUR($state['shipping_cost_excl']);
        $cart->shippingIncl = Money::EUR($state['shipping_cost_incl']);
        $cart->paymentExcl = Money::EUR($state['payment_cost_excl']);
        $cart->paymentIncl = Money::EUR($state['payment_cost_incl']);

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

    public function findLine(string $lineId): ?CartLine
    {
        foreach ($this->getLines() as $line) {
            if ($line->getLineId() === $lineId) {
                return $line;
            }
        }

        return null;
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
        return array_reduce((array)$this->getLines(), fn($carry, $line) => $carry + $line->getQuantity(), 0);
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

        return $includeTax ? $this->totalIncl : $this->totalExcl;
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

        return $includeTax ? $this->subtotalIncl : $this->subtotalExcl;
    }

    public function getShippingCost(?bool $includeTax = null): ?string
    {
        if (!$this->getShippingCostExcl()->isPositive()) {
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

        return $includeTax ? $this->getShippingCostIncl() : $this->getShippingCostExcl();
    }

    public function getPaymentCost(?bool $includeTax = null): ?string
    {
        if (!$this->getPaymentCostExcl()->isPositive()) {
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

        return $includeTax ? $this->getPaymentCostIncl() : $this->getPaymentCostExcl();
    }

    public function getDiscountPrice(?bool $includeTax = null): ?string
    {
        if (!$this->getDiscountTotalExcl()->isPositive()) {
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

        return $includeTax ? $this->discountIncl : $this->discountExcl;
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
        return $this->totalVat;
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

    public function getData(?string $key = null, $default = null): mixed
    {
        if (!$key) {
            return $this->data;
        }

        return $this->data($key, null, $default);
    }
}
