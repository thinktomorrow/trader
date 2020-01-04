<?php

declare(strict_types=1);

namespace Thinktomorrow\Trader\Reads\Purchase;

interface PurchasableItemRead
{
    public function title(): string;

    public function description(): string;

    public function salePrice(): string;

    // ...
}
