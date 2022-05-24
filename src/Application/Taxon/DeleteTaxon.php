<?php
declare(strict_types=1);

namespace Thinktomorrow\Trader\Application\Taxon;

use Thinktomorrow\Trader\Domain\Model\Taxon\TaxonId;

final class DeleteTaxon
{
    private string $taxon_id;

    public function __construct(string $taxon_id)
    {
        $this->taxon_id = $taxon_id;
    }

    public function getTaxonId(): TaxonId
    {
        return TaxonId::fromString($this->taxon_id);
    }
}
