<?php
declare(strict_types=1);

namespace Thinktomorrow\Trader\Domain\Model\Product\VariantTaxa;

use Thinktomorrow\Trader\Domain\Common\Entity\ChildEntity;
use Thinktomorrow\Trader\Domain\Model\Product\Variant\VariantId;
use Thinktomorrow\Trader\Domain\Model\Taxon\TaxonId;
use Thinktomorrow\Trader\Domain\Model\Taxonomy\TaxonomyId;
use Thinktomorrow\Trader\Domain\Model\Taxonomy\TaxonomyType;

class VariantTaxon implements ChildEntity
{
    public readonly VariantId $variantId;
    public readonly TaxonId $taxonId;
    public readonly TaxonomyId $taxonomyId;
    public readonly TaxonomyType $taxonomyType;

    private function __construct()
    {
    }

    public static function create(VariantId $variantId, TaxonomyId $taxonomyId, TaxonomyType $taxonomyType, TaxonId $taxonId): static
    {
        $object = new static();

        $object->variantId = $variantId;
        $object->taxonomyId = $taxonomyId;
        $object->taxonomyType = $taxonomyType;
        $object->taxonId = $taxonId;

        return $object;
    }

    public function getMappedData(): array
    {
        return [
            'variant_id' => $this->variantId->get(),
            'taxonomy_id' => $this->taxonomyId->get(),
            'taxonomy_type' => $this->taxonomyType->value,
            'taxon_id' => $this->taxonId->get(),
        ];
    }

    public static function fromMappedData(array $state, array $aggregateState, array $childEntities = []): static
    {
        $object = new static();

        $object->variantId = VariantId::fromString($aggregateState['variant_id']);
        $object->taxonomyId = TaxonomyId::fromString($state['taxonomy_id']);
        $object->taxonomyType = TaxonomyType::from($state['taxonomy_type']);
        $object->taxonId = TaxonId::fromString($state['taxon_id']);

        return $object;
    }
}
