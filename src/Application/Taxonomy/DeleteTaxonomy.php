<?php
declare(strict_types=1);

namespace Thinktomorrow\Trader\Application\Taxonomy;

use Thinktomorrow\Trader\Domain\Model\Taxonomy\TaxonomyId;

final class DeleteTaxonomy
{
    private string $taxonomyId;

    public function __construct(string $taxonomyId)
    {
        $this->taxonomyId = $taxonomyId;
    }

    public function getTaxonomyId(): TaxonomyId
    {
        return TaxonomyId::fromString($this->taxonomyId);
    }
}
