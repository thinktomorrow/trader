<?php

namespace Thinktomorrow\Trader\Application\Taxon\Category;

interface CategoryRepository
{
    public function findByKey(string $key): Category;
}
