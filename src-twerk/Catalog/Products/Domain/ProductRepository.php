<?php

namespace Thinktomorrow\Trader\Catalog\Products\Domain;

use Illuminate\Support\Collection;

interface ProductRepository
{
    public function findById(string $productId): Product;

    public function getByProductGroup(string $productGroupId): Collection;

    public function create(array $values): Product;

    public function save(string $productId, array $values): void;

    public function delete(string $productId): void;
}
