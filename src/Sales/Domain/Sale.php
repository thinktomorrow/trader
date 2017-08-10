<?php

namespace Thinktomorrow\Trader\Sales\Domain;

use Thinktomorrow\Trader\Order\Domain\Purchasable;

interface Sale
{
    public function __construct(SaleId $id, array $conditions,  array $adjusters);

    public function id(): SaleId;

    public function apply(Purchasable $purchasable);
}