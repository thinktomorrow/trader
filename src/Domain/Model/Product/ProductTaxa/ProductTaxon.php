<?php
declare(strict_types=1);

namespace Thinktomorrow\Trader\Domain\Model\Product\ProductTaxa;

use Thinktomorrow\Trader\Domain\Common\Entity\ChildEntity;
use Thinktomorrow\Trader\Domain\Common\Entity\HasData;
use Thinktomorrow\Trader\Domain\Model\Product\ProductId;
use Thinktomorrow\Trader\Domain\Model\Taxon\TaxonId;
use Thinktomorrow\Trader\Domain\Model\Taxonomy\TaxonomyId;
use Thinktomorrow\Trader\Domain\Model\Taxonomy\TaxonomyType;

class ProductTaxon implements ChildEntity
{
    use HasData;

    public readonly ProductId $productId;
    public readonly TaxonomyId $taxonomyId;
    public readonly TaxonomyType $taxonomyType;
    public readonly TaxonId $taxonId;

    private function __construct()
    {
    }

    public static function create(ProductId $productId, TaxonomyId $taxonomyId, TaxonomyType $taxonomyType, TaxonId $taxonId): static
    {
        $object = new static();

        $object->productId = $productId;
        $object->taxonomyId = $taxonomyId;
        $object->taxonomyType = $taxonomyType;
        $object->taxonId = $taxonId;

        return $object;
    }

    public function getMappedData(): array
    {
        return [
            'product_id' => $this->productId->get(),
            'taxonomy_id' => $this->taxonomyId->get(),
            'taxonomy_type' => $this->taxonomyType->value,
            'taxon_id' => $this->taxonId->get(),
            'data' => json_encode($this->data),
        ];
    }

    public static function fromMappedData(array $state, array $aggregateState, array $childEntities = []): static
    {
        $object = new static();

        $object->productId = ProductId::fromString($aggregateState['product_id']);
        $object->taxonomyId = TaxonomyId::fromString($state['taxonomy_id']);
        $object->taxonomyType = TaxonomyType::from($state['taxonomy_type']);
        $object->taxonId = TaxonId::fromString($state['taxon_id']);
        $object->data = json_decode($state['data'], true);

        return $object;
    }
}
