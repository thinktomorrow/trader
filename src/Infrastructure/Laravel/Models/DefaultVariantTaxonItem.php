<?php
declare(strict_types=1);

namespace Thinktomorrow\Trader\Infrastructure\Laravel\Models;

use Thinktomorrow\Trader\Application\Product\Taxa\VariantTaxonItem;
use Thinktomorrow\Trader\Domain\Model\Taxon\TaxonState;
use Thinktomorrow\Trader\Domain\Model\Taxonomy\TaxonomyType;

class DefaultVariantTaxonItem extends DefaultProductTaxonItem implements VariantTaxonItem
{
    protected string $variantId;

    public function __construct(string $variantId, string $productId, string $taxonId, string $taxonomyId, TaxonomyType $taxonomyType, bool $showsInGrid, TaxonState $taxonState, array $taxonKeys, array $data)
    {
        $this->variantId = $variantId;

        parent::__construct($productId, $taxonId, $taxonomyId, $taxonomyType, $showsInGrid, $taxonState, $taxonKeys, $data);
    }

    public static function fromMappedData(array $state, array $keys): static
    {
        return new static(
            $state['variant_id'],
            $state['product_id'],
            $state['taxon_id'],
            $state['taxonomy_id'],
            TaxonomyType::from($state['taxonomy_type']),
            (bool)$state['shows_in_grid'],
            TaxonState::from($state['state']),
            $keys,
            array_merge(
                ['taxonomy_data' => json_decode($state['taxonomy_data'], true)],
                ['taxon_data' => json_decode($state['taxon_data'], true)],
                json_decode($state['data'], true),
            ),
        );
    }

    public function getVariantId(): string
    {
        return $this->variantId;
    }
}
