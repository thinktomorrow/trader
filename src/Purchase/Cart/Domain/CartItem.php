<?php declare(strict_types=1);

namespace Thinktomorrow\Trader\Purchase\Cart\Domain;

use Money\Money;
use Thinktomorrow\Trader\Common\Notes\CarriesNotes;
use Thinktomorrow\Trader\Purchase\Discounts\Domain\Discountable;
use Thinktomorrow\Trader\Purchase\Items\Domain\PurchasableItemId;

interface CartItem extends CarriesNotes, Discountable
{
    public function purchasableItemId(): PurchasableItemId;

    public function total(): Money;

    public function quantity(): int;

    public function singleTotal(): Money;

    public function saleTotal(): Money;

    public function saleAndDiscountTotal(): Money;

    public function taxTotal(): Money;
}
