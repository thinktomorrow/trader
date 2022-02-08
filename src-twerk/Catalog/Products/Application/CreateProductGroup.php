<?php

namespace Thinktomorrow\Trader\Catalog\Products\Application;

use Illuminate\Contracts\Events\Dispatcher;
use Thinktomorrow\Trader\Catalog\Products\Domain\Events\ProductGroupCreated;
use Thinktomorrow\Trader\Catalog\Products\Domain\ProductGroup;
use Thinktomorrow\Trader\Catalog\Products\Domain\ProductGroupRepository;

class CreateProductGroup
{
    private ProductGroupRepository $productGroupRepository;
    private Dispatcher $eventDispatcher;

    public function __construct(ProductGroupRepository $productGroupRepository, Dispatcher $eventDispatcher)
    {
        $this->productGroupRepository = $productGroupRepository;
        $this->eventDispatcher = $eventDispatcher;
    }

    public function handle(array $taxonKeys, array $data): ProductGroup
    {
        $productGroup = $this->productGroupRepository->create([
            'data' => $data,
        ]);

        $this->productGroupRepository->syncTaxonomy($productGroup->getId(), $taxonKeys);

        $this->eventDispatcher->dispatch(new ProductGroupCreated($productGroup->getId()));

        // Refetch so that taxonomy is included
        return $this->productGroupRepository->findById($productGroup->getId());
    }
}
