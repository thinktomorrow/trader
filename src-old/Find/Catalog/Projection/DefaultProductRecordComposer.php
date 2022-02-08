<?php declare(strict_types=1);

namespace Find\Catalog\Projection;

use Find\Catalog\Domain\ProductId;

class DefaultProductRecordComposer implements ProductRecordComposer
{
    public function compose(ProductId $productId): ProductRecord
    {
        // Fetch product and variants from repository

        // Fetch any other details (such as stock, translations, images, ... )

        return new ProductRecord($productId->get(), [], [], []);
    }
}
