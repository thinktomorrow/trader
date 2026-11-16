<?php

declare(strict_types=1);

namespace Thinktomorrow\Trader\Application\Promo\ApplicableDiscounts;

use Thinktomorrow\Trader\Domain\Common\Price\ItemDiscountPrice;
use Thinktomorrow\Trader\Domain\Common\Price\TaxMode;
use Thinktomorrow\Trader\Domain\Model\Order\AppliedDiscount\AppliedDiscountId;
use Thinktomorrow\Trader\Domain\Model\Order\Line\Line;
use Thinktomorrow\Trader\Domain\Model\Order\Order;

interface ProductDiscount extends ApplicableDiscount
{
    public function isApplicable(Order $order, Line $line): bool;

    public function getDiscountPrice(Order $order, Line $line): ItemDiscountPrice;

    public function apply(Order $order, Line $line, AppliedDiscountId $appliedDiscountId): void;

    public function setCalculationTaxMode(TaxMode $taxMode): void;
}
