<?php
declare(strict_types=1);

namespace Thinktomorrow\Trader\Infrastructure\Test\Repositories;

use Thinktomorrow\Trader\Domain\Model\Product\Product;
use Thinktomorrow\Trader\Domain\Model\Product\ProductId;
use Thinktomorrow\Trader\Domain\Model\Product\Variant\VariantId;
use Thinktomorrow\Trader\Domain\Model\Product\ProductRepository;
use Thinktomorrow\Trader\Application\Cart\VariantForCart\VariantForCart;
use Thinktomorrow\Trader\Application\Product\ProductDetail\ProductDetail;
use Thinktomorrow\Trader\Application\Product\ProductOptions\ProductOptions;
use Thinktomorrow\Trader\Domain\Model\Product\Exceptions\CouldNotFindProduct;
use Thinktomorrow\Trader\Application\Product\ProductDetail\ProductDetailRepository;
use Thinktomorrow\Trader\Application\Product\ProductOptions\ProductOptionsRepository;

final class InMemoryProductDetailRepository implements ProductDetailRepository, ProductOptionsRepository
{
    public function findProductDetail(VariantId $variantId): ProductDetail
    {
        // TODO: Implement findProductDetail() method.
    }

    public function getProductOptions(ProductId $productId): ProductOptions
    {
        // TODO: Implement getProductOptions() method.
    }
}
