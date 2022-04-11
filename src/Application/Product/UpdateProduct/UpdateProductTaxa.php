<?php
declare(strict_types=1);

namespace Thinktomorrow\Trader\Application\Product\UpdateProduct;

use Thinktomorrow\Trader\Domain\Model\Taxon\TaxonId;
use Thinktomorrow\Trader\Domain\Model\Product\ProductId;

class UpdateProductTaxa
{
    private string $productId;
    private array $taxonIds;

    public function __construct(string $productId, array $taxonIds)
    {
        $this->productId = $productId;
        $this->taxonIds = $taxonIds;
    }

    public function getProductId(): ProductId
    {
        return ProductId::fromString($this->productId);
    }

    public function getTaxonIds(): array
    {
        return array_map(fn($taxon_id) => TaxonId::fromString($taxon_id), $this->taxonIds);
    }
}
