<?php

declare(strict_types=1);

namespace Thinktomorrow\Trader\Catalog\Products\Application;

use Thinktomorrow\Trader\Catalog\Products\Domain\Events\ProductMarkedAvailable;
use Thinktomorrow\Trader\Catalog\Products\Domain\Events\ProductMarkedUnavailable;
use Thinktomorrow\Trader\Catalog\Products\Domain\Events\ProductsReordered;
use Thinktomorrow\Trader\Catalog\Products\Domain\Product;
use Thinktomorrow\Trader\Catalog\Products\Domain\ProductGroup;
use Thinktomorrow\Trader\Catalog\Products\Domain\ProductGroupRepository;
use Thinktomorrow\Trader\Catalog\Products\Domain\ProductRepository;

final class MarkFirstAvailableProductAsGridProduct
{
    private ProductGroupRepository $productGroupRepository;
    private ProductRepository $productRepository;

    public function __construct(ProductGroupRepository $productGroupRepository, ProductRepository $productRepository)
    {
        $this->productGroupRepository = $productGroupRepository;
        $this->productRepository = $productRepository;
    }

    /**
     * Get first available product and mark this as the grid product
     * All others should be unmarked as grid product
     *
     * @param ProductMarkedAvailable|ProductMarkedUnavailable $event
     */
    public function handle($event): void
    {
        $productGroup = $this->productGroupRepository->findByProductId($event->productId);

        $this->mark($productGroup);
    }

    public function onProductsReordered(ProductsReordered $event): void
    {
        $productGroup = $this->productGroupRepository->findById($event->productGroupId);

        $this->mark($productGroup);
    }

    private function mark(ProductGroup $productGroup): void
    {
        $marked = false;

        /** @var Product $product */
        foreach ($productGroup->getProducts() as $product) {
            if (! $marked && $product->isAvailable()) {
                $this->productRepository->save($product->getId(), ['is_grid_product' => 1]);
                $marked = true;

                continue;
            }

            // Unmark all others as grid product...
            $this->productRepository->save($product->getId(), ['is_grid_product' => 0]);
        }
    }
}
