<?php

namespace Thinktomorrow\Trader\Order\Domain;

use Money\Money;
use Thinktomorrow\Trader\Discounts\Domain\Discountable;
use Thinktomorrow\Trader\Taxes\Taxable;
use Thinktomorrow\Trader\Taxes\TaxRate;

interface OrderProduct extends Discountable, Taxable
{
    public function getId();

    public function getProductId();

    public function getOrderReference(): OrderReference;

    public function getQuantity(): int;

    public function replaceQuantity(int $quantity): void;

    public function getTotal(): Money;

    /**  Subtotal including the product sales discount */
    public function getSalesSubTotal(): Money;

    /** Total without product sales / global discounts */
    public function getSubTotal(): Money;

    public function getUnitPrice(): Money;

    public function getTaxRate(): TaxRate;

    public function getTaxTotal(): Money;

    public function isTaxApplicable(): bool;
}
