<?php
declare(strict_types=1);

namespace Thinktomorrow\Trader\Application\Product\UpdateProduct;

use Thinktomorrow\Trader\Domain\Model\Product\ProductId;
use Thinktomorrow\Trader\Domain\Model\Product\ProductTaxa\ProductTaxon;
use Thinktomorrow\Trader\Domain\Model\Taxon\TaxonId;
use Thinktomorrow\Trader\Domain\Model\Taxonomy\TaxonomyId;

class UpdateProductTaxa
{
    private string $productId;
    private array $taxonIds;
    private array $scopedTaxonomyIds;

    public function __construct(string $productId, array $taxonIds, array $scopedTaxonomyIds = [])
    {
        $this->productId = $productId;
        $this->taxonIds = $taxonIds;
        $this->scopedTaxonomyIds = $scopedTaxonomyIds;
    }

    public function getProductId(): ProductId
    {
        return ProductId::fromString($this->productId);
    }

    /**
     * Limit the update to any of the given taxonomy IDs. This
     * will keep other taxa assigned to the product intact.
     */
    public function getScopedTaxonomyIds(): array
    {
        return array_map(fn ($taxonomyId) => TaxonomyId::fromString($taxonomyId), $this->scopedTaxonomyIds);
    }

    public function getProductTaxa(): array
    {
        return array_map(function ($taxonId) {
            return ProductTaxon::create($this->getProductId(), $taxonId);
        }, $this->getTaxonIds());
    }

    public function getTaxonIds(): array
    {
        return array_map(fn ($taxon_id) => TaxonId::fromString($taxon_id), $this->taxonIds);
    }
}
