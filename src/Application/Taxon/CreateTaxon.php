<?php
declare(strict_types=1);

namespace Thinktomorrow\Trader\Application\Taxon;

use Thinktomorrow\Trader\Domain\Common\Locale;
use Thinktomorrow\Trader\Domain\Model\Taxon\TaxonId;
use Thinktomorrow\Trader\Domain\Model\Taxon\TaxonKeyId;
use Thinktomorrow\Trader\Domain\Model\Taxonomy\TaxonomyId;

final class CreateTaxon
{
    private string $taxonomyId;
    private string $taxonKeyId;
    private string $taxonKeyLocale;
    private array $data;
    private ?string $parent_taxon_id;

    public function __construct(string $taxonomyId, string $taxonKeyId, string $taxonKeyLocale, array $data, ?string $parent_taxon_id = null)
    {
        $this->taxonomyId = $taxonomyId;
        $this->taxonKeyId = $taxonKeyId;
        $this->taxonKeyLocale = $taxonKeyLocale;
        $this->data = $data;
        $this->parent_taxon_id = $parent_taxon_id;
    }

    public function getTaxonomyId(): TaxonomyId
    {
        return TaxonomyId::fromString($this->taxonomyId);
    }

    public function getTaxonKeyId(): TaxonKeyId
    {
        return TaxonKeyId::fromString($this->taxonKeyId);
    }

    public function getTaxonKeyLocale(): Locale
    {
        return Locale::fromString($this->taxonKeyLocale);
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
