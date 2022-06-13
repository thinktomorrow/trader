<?php
declare(strict_types=1);

namespace Thinktomorrow\Trader\Application\Product\Grid;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Thinktomorrow\Trader\Domain\Common\Locale;

interface GridRepository
{
    public function filterByTerm(string $term): static;

    public function filterByTaxonKeys(array $taxonKeys): static;

    public function filterByTaxonIds(array $taxon_ids): static;

    public function filterByProductIds(array $product_ids): static;

    public function filterByPrice(string $minimumPriceAmount = null, string $maximumPriceAmount = null): static;

    public function sortByLabel(): static;

    public function sortByLabelDesc(): static;

    public function sortByPrice(): static;

    public function sortByPriceDesc(): static;

    public function paginate(int $perPage): static;

    public function limit(int $limit): static;

    public function setLocale(Locale $locale): static;

    public function getResults(): LengthAwarePaginator;
}
