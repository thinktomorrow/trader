<?php
declare(strict_types=1);

namespace Thinktomorrow\Trader\Domain\Model\Product;

use Assert\Assertion;
use Thinktomorrow\Trader\Domain\Model\Product\Events\ProductTaxaUpdated;
use Thinktomorrow\Trader\Domain\Model\Taxon\TaxonId;

trait BelongsToTaxa
{
    private array $taxonIds = [];

    public function getTaxonIds(): array
    {
        return $this->taxonIds;
    }

    public function updateTaxonIds(array $taxonIds): void
    {
        Assertion::allIsInstanceOf($taxonIds, TaxonId::class);

        $this->taxonIds = $taxonIds;

        $this->recordEvent(new ProductTaxaUpdated($this->productId));
    }
}
