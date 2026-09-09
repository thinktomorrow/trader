<?php

declare(strict_types=1);

namespace Thinktomorrow\Trader\Application\Taxonomy;

use Thinktomorrow\Trader\Domain\Model\Taxonomy\TaxonomyId;

interface TaxonomyItemRepository
{
    /** @return TaxonomyItem[] */
    public function getForFilter(): array;

    public function findForFilter(TaxonomyId $taxonomyId): TaxonomyItem;
}
