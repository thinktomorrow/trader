<?php
declare(strict_types=1);

namespace Thinktomorrow\Trader\Infrastructure\Laravel\Models\Cart;

use Thinktomorrow\Trader\Application\Cart\Read\Cart;
use Thinktomorrow\Trader\Application\Cart\Read\CartBillingAddress;
use Thinktomorrow\Trader\Application\Cart\Read\CartLine;
use Thinktomorrow\Trader\Application\Cart\Read\CartPayment;
use Thinktomorrow\Trader\Application\Cart\Read\CartShipping;
use Thinktomorrow\Trader\Application\Cart\Read\CartShippingAddress;
use Thinktomorrow\Trader\Application\Cart\Read\CartShopper;
use Thinktomorrow\Trader\Application\Common\RendersData;
use Thinktomorrow\Trader\Application\Common\RendersMoney;
use Thinktomorrow\Trader\Infrastructure\Laravel\Models\OrderRead\WithFormattedOrderTotals;
use Thinktomorrow\Trader\Infrastructure\Laravel\Models\OrderRead\WithImmutableOrderTotals;

class DefaultCart implements Cart
{
    use RendersData;
    use RendersMoney;
    use WithImmutableOrderTotals;
    use WithFormattedOrderTotals;

    protected string $orderId;
    protected iterable $lines;
    protected ?CartShippingAddress $shippingAddress;
    protected ?CartBillingAddress $billingAddress;
    protected ?CartShipping $shipping;
    protected ?CartPayment $payment;
    protected ?CartShopper $shopper;
    protected array $discounts;
    protected array $data;

    final private function __construct()
    {
    }

    public static function fromMappedData(array $state, array $childObjects, array $discounts): static
    {
        $cart = new static();

        $cart->orderId = $state['order_id'];

        $cart->initializeOrderTotalsFromState($state);

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
        return array_reduce((array)$this->getLines(), fn ($carry, $line) => $carry + $line->getQuantity(), 0);
    }

    public function isVatExempt(): bool
    {
        return $this->dataAsPrimitive('is_vat_exempt') ?? false;
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
        if (! $key) {
            return $this->data;
        }

        return $this->data($key, null, $default);
    }
}
