<?php
declare(strict_types=1);

namespace Thinktomorrow\Trader\Catalog\Products\Domain;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Money\Money;

interface GridRepository
{
    public function filterByTerm(string $term): self;

    public function filterByTaxa(array $taxa): self;

    public function filterByProductGroupIds(array $productGroupIds): self;

    public function filterByPrice(Money $minimumPrice = null, Money $maximumPrice = null): self;

    public function sortByLabel(): self;

    public function sortByLabelDesc(): self;

    public function sortByPrice(): self;

    public function sortByPriceDesc(): self;

    public function paginate(int $perPage): self;

    public function limit(int $limit): self;

    public function getResults(): LengthAwarePaginator;
}
