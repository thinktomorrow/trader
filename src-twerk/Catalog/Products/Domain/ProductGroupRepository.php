<?php

namespace Thinktomorrow\Trader\Catalog\Products\Domain;

interface ProductGroupRepository
{
    public function findById(string $productGroupId): ProductGroup;

    public function findByProductId(string $productId): ProductGroup;

    public function create(array $values): ProductGroup;

    public function syncTaxonomy(string $productGroupId, array $taxonKeys): void;

    public function save(ProductGroup $productGroup): void;

    public function delete(ProductGroup $productGroup): void;
}
