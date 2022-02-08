<?php declare(strict_types=1);

namespace Thinktomorrow\Trader\Catalog\Products\Projection;

use Thinktomorrow\Trader\Catalog\Products\Domain\ProductId;

interface ProductRecordComposer
{
    public function compose(ProductId $productId): ProductRecord;
}
