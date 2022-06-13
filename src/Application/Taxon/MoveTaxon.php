<?php
declare(strict_types=1);

namespace Thinktomorrow\Trader\Application\Taxon;

use Thinktomorrow\Trader\Domain\Model\Taxon\TaxonId;

final class MoveTaxon
{
    private string $taxon_id;
    private ?string $parent_taxon_id;

    public function __construct(string $taxon_id, ?string $parent_taxon_id = null)
    {
        $this->taxon_id = $taxon_id;
        $this->parent_taxon_id = $parent_taxon_id;
    }

    public function getTaxonId(): TaxonId
    {
        return TaxonId::fromString($this->taxon_id);
    }

    public function hasParentTaxonId(): bool
    {
        return ! is_null($this->parent_taxon_id);
    }

    public function getParentTaxonId(): ?TaxonId
    {
        return $this->parent_taxon_id ? TaxonId::fromString($this->parent_taxon_id) : null;
    }
}
