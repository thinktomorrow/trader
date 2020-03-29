<?php declare(strict_types=1);

namespace Thinktomorrow\Trader\Find\Catalog\Projection;

use Thinktomorrow\Trader\Find\Catalog\Domain\ProductId;

interface ProductRecordComposer
{
    public function compose(ProductId $productId): ProductRecord;
}
