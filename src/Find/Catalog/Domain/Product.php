<?php

declare(strict_types=1);

namespace Thinktomorrow\Trader\Find\Catalog\Domain;

class Product
{
    /** @var ProductVariantId */
    private $productVariantId;

    public function __construct(ProductVariantId $productVariantId)
    {
        $this->productVariantId = $productVariantId;
    }

    public function getProductVariantIds(): ProductVariantId
    {
        return $this->productVariantId;
    }

    // title, description, seo
    // images
    // variants: image, option, price, amount, sku
    // channel availability
    // type
    // vendor
    // collections (category pages)
    // tags
    // sales

    // extra:
    // product series
    // product line
    // custom attribute: cnk (unique)
    // ean
    // product bundle

}
