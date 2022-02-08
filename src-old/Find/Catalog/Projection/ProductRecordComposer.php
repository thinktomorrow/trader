<?php declare(strict_types=1);

namespace Find\Catalog\Projection;

use Find\Catalog\Domain\ProductId;

interface ProductRecordComposer
{
    public function compose(ProductId $productId): ProductRecord;
}
