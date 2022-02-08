<?php declare(strict_types=1);

namespace Thinktomorrow\Trader\Order\Domain;

use Thinktomorrow\Trader\Order\Domain\ShippingState;
use Money\Money;
use Thinktomorrow\Trader\Common\Address\Address;
use Thinktomorrow\Trader\Common\Cash\RendersMoney;
use Thinktomorrow\Trader\Discounts\Domain\AppliedDiscountCollection;
use Thinktomorrow\Trader\Discounts\Domain\Discountable;
use Thinktomorrow\Trader\Taxes\Taxable;
use Thinktomorrow\Trader\Taxes\TaxRate;

class OrderShipping implements Discountable, Taxable
{
    use MethodDefaults;
    use RendersMoney;

    private ?string $id;
    private string $method;
    private ShippingState $shippingState;
    private Money $subTotal;
    private TaxRate $taxRate;
    private AppliedDiscountCollection $discounts;
    private Address $address;
    private array $data;

    public function __construct(?string $id, string $method, ShippingState $shippingState, Money $subTotal, TaxRate $taxRate, AppliedDiscountCollection $discounts, Address $address, array $data)
    {
        if (! is_null($id) && ! $id) {
            throw new \InvalidArgumentException('empty strings for id value is not allowed. Use null instead');
        }

        if (! $method) {
            throw new \InvalidArgumentException('The method parameter cannot be empty');
        }

        $this->id = $id;
        $this->method = $method;
        $this->shippingState = $shippingState;
        $this->subTotal = $subTotal;
        $this->taxRate = $taxRate;
        $this->discounts = $discounts;
        $this->address = $address;
        $this->data = $data;

        foreach ($discounts as $discount) {
            $this->addDiscount($discount);
        }
    }

    public static function empty(): self
    {
        return new static(null, 'unknown', ShippingState::fromString(ShippingState::UNKNOWN), Money::EUR(0), TaxRate::default(), new AppliedDiscountCollection(), Address::empty(), []);
    }

    public function getShippingState(): ShippingState
    {
        return $this->shippingState;
    }

    public function replaceSubTotal(Money $subTotal): self
    {
        return new static($this->id, $this->method, $this->shippingState, $subTotal, $this->taxRate, $this->discounts, $this->address, $this->data);
    }

    public function replaceMethod(string $method): self
    {
        return new static($this->id, $method, $this->shippingState, $this->subTotal, $this->taxRate, $this->discounts, $this->address, $this->data);
    }

    public function replaceShippingState(ShippingState $shippingState): self
    {
        return new static($this->id, $this->method, $shippingState, $this->subTotal, $this->taxRate, $this->discounts, $this->address, $this->data);
    }

    public function replaceAddress(Address $address): self
    {
        return new static($this->id, $this->method, $this->shippingState, $this->subTotal, $this->taxRate, $this->discounts, $address, $this->data);
    }

    public function replaceData(array $data): self
    {
        return new static($this->id, $this->method, $this->shippingState, $this->subTotal, $this->taxRate, $this->discounts, $this->address, array_merge($this->data, $data));
    }

    public function requiresAddress(): bool
    {
        return true;
    }

    public function hasAddress(): bool
    {
        return ! $this->address->isEmpty();
    }

    public function getAddress(): Address
    {
        return $this->address;
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'method' => $this->method,
            'shippingState' => $this->getShippingState()->get(),
            'total' => (int)$this->getTotal()->getAmount(),
            'discounttotal' => (int)$this->getDiscountTotal()->getAmount(),
            'subtotal' => (int)$this->getSubTotal()->getAmount(),
            'taxtotal' => (int)$this->getTaxTotal()->getAmount(),
            'discounts' => $this->discounts->toArray(),
            'taxrate' => (int)$this->getTaxRate()->toPercentage()->toInteger(),
            'address' => $this->address->toArray(),
            'data' => $this->data,
        ];
    }
}
