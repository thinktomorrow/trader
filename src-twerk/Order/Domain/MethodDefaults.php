<?php declare(strict_types=1);

namespace Thinktomorrow\Trader\Order\Domain;

use Illuminate\Support\Arr;
use Money\Money;
use Thinktomorrow\Trader\Common\Cash\Cash;
use Thinktomorrow\Trader\Common\Domain\Locale;
use Thinktomorrow\Trader\Discounts\Domain\AppliedDiscount;
use Thinktomorrow\Trader\Discounts\Domain\AppliedDiscountCollection;
use Thinktomorrow\Trader\Taxes\TaxRate;

trait MethodDefaults
{
    public function getId(): ?string
    {
        return $this->id;
    }

    /** does it already exist as record in storage */
    public function exists(): bool
    {
        return ! ! $this->id;
    }

    public function getMethod(): string
    {
        return $this->method;
    }

    public function hasMethod(): bool
    {
        return ! ! ($this->method);
    }

    public function getTotal(): Money
    {
        $total = $this->isTaxApplicable() ? $this->getTotalIncludingTax() : $this->getTotalIncludingTax()->subtract($this->getTaxTotal());

        if ($total->isNegative()) {
            report(new \DomainException('Cart shipping/payment total dropped under zero [' . $total->getAmount() . ']'));
            $total = Money::EUR(0);
        }

        return $total;
    }

    private function getTotalIncludingTax(): Money
    {
        return $this->getSubTotal()->subtract($this->getDiscountTotal());
    }

    public function isFree(): bool
    {
        return $this->getTotal()->isZero();
    }

    public function getDiscountTotal(): Money
    {
        return array_reduce($this->discounts->all(), function ($carry, AppliedDiscount $discount) {
            return $carry->add($discount->getTotal());
        }, Money::EUR(0));
    }

    public function getSubTotal(): Money
    {
        return $this->subTotal;
    }

    public function getTaxRate(): TaxRate
    {
        return $this->taxRate;
    }

    public function getTaxTotal(): Money
    {
        $nettTotal = Cash::from($this->getTotalIncludingTax())->subtractTaxPercentage($this->getTaxRate()->toPercentage());

        return $this->getTotalIncludingTax()->subtract($nettTotal);
    }

    public function getTaxableTotal(): Money
    {
        return $this->getTotalIncludingTax();
    }

    private function isTaxApplicable(): bool
    {
        return $this->data['is_tax_applicable'] ?? true;
    }

    private function getLocale(): Locale
    {
        return $this->data['locale'] ?: Locale::default();
    }

    public function getTotalAsString(): string
    {
        return $this->renderMoney($this->getTotal(), $this->getLocale());
    }

    public function getDiscountTotalAsString(): string
    {
        return $this->renderMoney($this->getDiscountTotal(), $this->getLocale());
    }

    public function getSubTotalAsString(): string
    {
        return $this->renderMoney($this->getSubTotal(), $this->getLocale());
    }

    public function getTaxTotalAsString(): string
    {
        return $this->renderMoney($this->getTaxTotal(), $this->getLocale());
    }

    /**
     * Total amount on which the discounts should be calculated.
     *
     * @param array $conditions
     * @return Money
     */
    public function getDiscountableTotal(array $conditions): Money
    {
        return $this->getSubTotal();
    }

    public function getDiscountableQuantity(array $conditions): int
    {
        return 1;
    }

    public function getDiscounts(): AppliedDiscountCollection
    {
        return $this->discounts;
    }

    public function addDiscount(AppliedDiscount $discount)
    {
        $this->discounts->addItem($discount);
    }

    public function replaceData($key, $value): self
    {
        Arr::set($this->data, $key, $value);

        return $this;
    }

    /**
     * Get the instance as an array.
     *
     * @return array
     */
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'method' => $this->method,
            'total' => (int)$this->getTotal()->getAmount(),
            'discount_total' => (int)$this->getDiscountTotal()->getAmount(),
            'subtotal' => (int)$this->getSubTotal()->getAmount(),
            'tax_total' => (int)$this->getTaxTotal()->getAmount(),
            'discounts' => $this->getDiscounts()->toArray(),
            'tax_rate' => $this->getTaxRate()->toPercentage()->toInteger(),
            'address' => $this->data['address'],
            'data' => $this->data,
        ];
    }
}
