<?php
declare(strict_types=1);

namespace Thinktomorrow\Trader\Catalog\Products\Application;

use Thinktomorrow\Trader\Catalog\Products\Domain\Events\ProductQueuedForDeletion;
use Thinktomorrow\Trader\Catalog\Products\Domain\ProductRepository;

class DeleteProduct
{
    private ProductRepository $productRepository;

    public function __construct(ProductRepository $productRepository)
    {
        $this->productRepository = $productRepository;
    }

    public function onProductQueuedForDeletion(ProductQueuedForDeletion $event): void
    {
        $this->productRepository->delete($event->productId);
    }
}
