<?php
declare(strict_types=1);

namespace Thinktomorrow\Trader\Infrastructure\Test\Repositories;

use Thinktomorrow\Trader\Application\Product\ProductTaxa\ProductTaxonRepository;
use Thinktomorrow\Trader\Infrastructure\Laravel\Models\DefaultProductTaxonRead;

final class InMemoryProductTaxonRepository implements ProductTaxonRepository
{
    // Key: productId, value: array of taxonIds
    public static array $productTaxonLookup = [];

    public function getTaxaByProduct(string $productId): array
    {
        $taxonIds = static::$productTaxonLookup[$productId] ?? [];

        $taxa = InMemoryTaxonRepository::$taxons;
        $taxonomies = InMemoryTaxonomyRepository::$taxonomies;

        $result = [];

        foreach ($taxonIds as $taxonId) {
            if (isset($taxa[$taxonId])) {
                $taxon = $taxa[$taxonId];

                $result[] = DefaultProductTaxonRead::fromMappedData([
                    'product_id' => $productId,
                    'taxon_id' => $taxon->taxonId->get(),
                    'taxonomy_id' => $taxon->taxonomyId->get(),
                    'taxonomy_type' => $taxonomies[$taxon->taxonomyId->get()]->getType()->value,
                    'shows_in_grid' => $taxonomies[$taxon->taxonomyId->get()]->showsInGrid(),
                    'state' => $taxon->getMappedData()['state'],
                    'order' => $taxon->getMappedData()['order'],
                    'keys' => json_encode(array_map(fn ($taxonKey) => $taxonKey->getMappedData(), $taxon->getTaxonKeys())),
                    'data' => json_encode($taxon->getData()),
                ]);
            }
        }

        return $result;
    }

    public static function clear()
    {
        static::$productTaxonLookup = [];
    }
}
