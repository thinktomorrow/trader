<?php
declare(strict_types=1);

namespace Thinktomorrow\Trader\Infrastructure\Test\Repositories;

use Thinktomorrow\Trader\Application\Product\ProductDetail\ProductDetailRepository;
use Thinktomorrow\Trader\Domain\Model\Product\Exceptions\CouldNotFindVariant;
use Thinktomorrow\Trader\Domain\Model\Product\ProductState;
use Thinktomorrow\Trader\Domain\Model\Product\Variant\VariantId;
use Thinktomorrow\Trader\Domain\Model\Stock\StockItem;
use Thinktomorrow\Trader\Infrastructure\Laravel\Models\DefaultProductDetail;

final class InMemoryProductDetailRepository implements ProductDetailRepository
{
    public function findProductDetail(VariantId $variantId, bool $allowOffline = false): DefaultProductDetail
    {
        $variant = InMemoryVariantRepository::$variants[$variantId->get()];
        $product = InMemoryProductRepository::$products[$variant->productId->get()];
        $stock = InMemoryVariantRepository::$stockItems[$variantId->get()] ?? StockItem::fromMappedData([
            'stockitem_id' => $variantId->get(),
            'stock_level' => 5,
            'ignore_out_of_stock' => false,
            'stock_data' => json_encode([]),
        ]);

        if(!$allowOffline && !in_array($product->getState(), ProductState::onlineStates())) {
            throw new CouldNotFindVariant('No online variant found by id [' . $variantId->get(). ']');
        }

        return DefaultProductDetail::fromMappedData(array_merge(($stock->getMappedData()), $variant->getMappedData(), [
            'product_data' => json_encode($product->getData()),
            'taxon_ids' => array_map(fn ($taxonId) => $taxonId->get(), $product->getTaxonIds()),
        ]));
    }
}
