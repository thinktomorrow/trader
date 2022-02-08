<?php
declare(strict_types=1);

namespace Thinktomorrow\Trader\Order\Ports;

use Money\Money;
use Thinktomorrow\Trader\Common\Cash\Cash;
use Thinktomorrow\Trader\Discounts\Domain\AppliedDiscount;
use Thinktomorrow\Trader\Discounts\Domain\AppliedDiscountCollection;
use Thinktomorrow\Trader\Order\Domain\OrderProduct as OrderProductContract;
use Thinktomorrow\Trader\Order\Domain\OrderReference;
use Thinktomorrow\Trader\Taxes\TaxRate;

class DefaultOrderProduct implements OrderProductContract
{
    private ?string $id;
    private string $productId;
    private OrderReference $orderReference;
    private int $quantity;
    private Money $unitPrice; // unit price including vat
    private TaxRate $taxRate;
    private bool $isTaxApplicable;
    private array $data;
    private AppliedDiscountCollection $discounts;

    public function __construct(
        ?string $id,
        string $productId,
        OrderReference $orderReference,
        int $quantity,
        Money $unitPrice,
        TaxRate $taxRate,
        bool $isTaxApplicable,
        array $data
    ) {
        if (! is_null($id) && ! $id) {
            throw new \InvalidArgumentException('empty strings for id value is not allowed. Use null instead');
        }

        if (! $productId) {
            throw new \InvalidArgumentException('A valid productId must be passed. ['.$productId.'] was passed instead.');
        }

        $this->id = $id;
        $this->productId = $productId;
        $this->orderReference = $orderReference;
        $this->quantity = $quantity;
        $this->unitPrice = $unitPrice;
        $this->taxRate = $taxRate;
        $this->isTaxApplicable = $isTaxApplicable;
        $this->data = $data;

        $this->discounts = new AppliedDiscountCollection();
    }

    public function getId(): ?string
    {
        return $this->id;
    }

    /** does it already exist as orderCustomer record in storage */
    public function exists(): bool
    {
        return ! ! $this->id;
    }

    public function getProductId(): string
    {
        return $this->productId;
    }

    public function getOrderReference(): OrderReference
    {
        return $this->orderReference;
    }

    public function getQuantity(): int
    {
        return $this->quantity;
    }

    public function replaceQuantity(int $quantity): void
    {
        $this->quantity = $quantity;
    }

    public function getTotal(): Money
    {
        $total = $this->isTaxApplicable()
            ? $this->getTotalIncludingTax()
            : $this->getTotalIncludingTax()->subtract($this->getTaxTotal());

        if ($total->isNegative()) {
            return Money::EUR(0);
        }

        return $total;
    }

    /**
     * For now, we assume the unit price is given with tax included.
     * If this should not be the case and the price is passed
     * without tax, here's where we will do the tweaking
     *
     * @return Money
     */
    private function getTotalIncludingTax(): Money
    {
        return $this->getSubTotal()->subtract($this->getDiscountTotal());
    }

    public function getSalesSubTotal(): Money
    {
        // TODO: subtract the sales discount ->subtract($this->discounts()->getproductSalesDiscount());
        return $this->getSubTotal();
    }

    public function getSubTotal(): Money
    {
        return $this->getUnitPrice()->multiply($this->getQuantity());
    }

    public function getUnitPrice(): Money
    {
        return $this->unitPrice;
    }

    public function getDiscountableTotal(array $conditions): Money
    {
        return $this->getSubTotal();
    }

    public function getDiscountableQuantity(array $conditions): int
    {
        return $this->getQuantity();
    }

    public function getDiscountTotal(): Money
    {
        return array_reduce($this->getDiscounts()->all(), function ($carry, AppliedDiscount $discount) {
            return $carry->add($discount->getTotal());
        }, Cash::zero());
    }

    public function getDiscounts(): AppliedDiscountCollection
    {
        return $this->discounts;
    }

    public function addDiscount(AppliedDiscount $discount)
    {
        // TODO: Implement addDiscount() method.
    }

    public function getTaxTotal(): Money
    {
        $nettTotal = Cash::from($this->getTotalIncludingTax())->subtractTaxPercentage($this->getTaxRate()->toPercentage());

        $taxTotal = $this->getTotalIncludingTax()->subtract($nettTotal);

        // In case that the discount makes the total drop under zero,
        // we'll need to address this on the tax total as well.
        if ($taxTotal->isNegative()) {
            return Cash::zero();
        }

        return $taxTotal;
    }

    public function getTaxRate(): TaxRate
    {
        return $this->taxRate;
    }

    public function isTaxApplicable(): bool
    {
        return $this->isTaxApplicable;
    }

    public function getTaxableTotal(): Money
    {
        return $this->getTotalIncludingTax();
    }
}
