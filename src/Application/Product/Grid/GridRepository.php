<?php
declare(strict_types=1);

namespace Thinktomorrow\Trader\Application\Product\Grid;

use Thinktomorrow\Trader\Domain\Common\Locale;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

interface GridRepository
{
    public function filterByTerm(string $term): self;

    public function filterByTaxa(array $taxa): self;

    public function filterByProductIds(array $productIds): self;

    public function filterByPrice(string $minimumPriceAmount = null, string $maximumPriceAmount = null): self;

    public function sortByLabel(): self;

    public function sortByLabelDesc(): self;

    public function sortByPrice(): self;

    public function sortByPriceDesc(): self;

    public function paginate(int $perPage): self;

    public function limit(int $limit): self;

    public function setLocale(Locale $locale): static;

    public function getResults(): LengthAwarePaginator;
}
