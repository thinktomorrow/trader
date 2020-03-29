<?php declare(strict_types=1);

namespace Thinktomorrow\Trader\Purchase\Cart\Domain;

use Thinktomorrow\Trader\Purchase\Notes\Domain\ContainsNotes;
use Thinktomorrow\Trader\Purchase\Discounts\Domain\Discountable;
use Thinktomorrow\Trader\Purchase\Items\Domain\PurchasableItemId;

interface CartItem extends ContainsNotes, Discountable
{
    public function purchasableItemId(): PurchasableItemId;

    //
}
