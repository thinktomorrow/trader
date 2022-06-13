<?php
declare(strict_types=1);

namespace Thinktomorrow\Trader\Infrastructure\Test\Repositories;

use Thinktomorrow\Trader\Application\Product\ProductDetail\ProductDetailRepository;
use Thinktomorrow\Trader\Domain\Model\Product\Variant\VariantId;
use Thinktomorrow\Trader\Infrastructure\Laravel\Models\DefaultProductDetail;

final class InMemoryProductDetailRepository implements ProductDetailRepository
{
    public function findProductDetail(VariantId $variantId): DefaultProductDetail
    {
        $variant = InMemoryVariantRepository::$variants[$variantId->get()];
        $product = InMemoryProductRepository::$products[$variant->productId->get()];

        return DefaultProductDetail::fromMappedData(array_merge($variant->getMappedData(), [
            'product_data' => json_encode($product->getData()),
            'taxon_ids' => array_map(fn ($taxonId) => $taxonId->get(), $product->getTaxonIds()),
        ]));
    }
}
