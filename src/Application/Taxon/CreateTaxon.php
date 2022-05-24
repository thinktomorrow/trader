<?php
declare(strict_types=1);

namespace Thinktomorrow\Trader\Application\Taxon;

use Thinktomorrow\Trader\Domain\Model\Taxon\TaxonId;
use Thinktomorrow\Trader\Domain\Model\Taxon\TaxonKey;

final class CreateTaxon
{
    private string $taxon_key;
    private array $data;
    private ?string $parent_taxon_id;

    public function __construct(string $taxon_key, array $data, ?string $parent_taxon_id = null)
    {
        $this->taxon_key = $taxon_key;
        $this->data = $data;
        $this->parent_taxon_id = $parent_taxon_id;
    }

    public function getTaxonKey(): TaxonKey
    {
        return TaxonKey::fromString($this->taxon_key);
    }

    public function getData(): array
    {
        return $this->data;
    }

    public function getParentTaxonId(): ?TaxonId
    {
        return $this->parent_taxon_id ? TaxonId::fromString($this->parent_taxon_id) : null;
    }
}
