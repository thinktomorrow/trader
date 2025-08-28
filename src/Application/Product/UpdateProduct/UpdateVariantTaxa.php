<?php
declare(strict_types=1);

namespace Thinktomorrow\Trader\Application\Product\UpdateProduct;

use Thinktomorrow\Trader\Domain\Model\Product\ProductId;
use Thinktomorrow\Trader\Domain\Model\Product\Variant\VariantId;
use Thinktomorrow\Trader\Domain\Model\Product\VariantTaxa\VariantTaxon;
use Thinktomorrow\Trader\Domain\Model\Taxon\TaxonId;

class UpdateVariantTaxa
{
    private string $productId;
    private string $variantId;
    private array $taxonIds;

    public function __construct(string $productId, string $variantId, array $taxonIds)
    {
        $this->productId = $productId;
        $this->variantId = $variantId;
        $this->taxonIds = $taxonIds;
    }

    public function getProductId(): ProductId
    {
        return ProductId::fromString($this->productId);
    }

    public function getVariantId(): VariantId
    {
        return VariantId::fromString($this->variantId);
    }

    public function getVariantTaxa(): array
    {
        return array_map(function ($taxonId) {
            return VariantTaxon::create($this->getVariantId(), $taxonId);
        }, $this->getTaxonIds());
    }

    public function getTaxonIds(): array
    {
        return array_map(fn ($taxon_id) => TaxonId::fromString($taxon_id), $this->taxonIds);
    }
}
