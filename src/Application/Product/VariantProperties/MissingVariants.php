<?php
declare(strict_types=1);

namespace Thinktomorrow\Trader\Application\Product\VariantProperties;

use Illuminate\Support\Collection;
use Thinktomorrow\Trader\Domain\Model\Product\Product;
use Thinktomorrow\Trader\Domain\Model\Product\ProductTaxa\VariantProperty;
use Thinktomorrow\Trader\Domain\Model\Product\Variant\Variant;
use Thinktomorrow\Trader\Domain\Model\Taxon\Taxon;
use Thinktomorrow\Trader\Domain\Model\Taxon\TaxonRepository;
use Thinktomorrow\Trader\Domain\Model\Taxonomy\Taxonomy;
use Thinktomorrow\Trader\Domain\Model\Taxonomy\TaxonomyRepository;

class MissingVariants
{
    private TaxonRepository $taxonRepository;
    private TaxonomyRepository $taxonomyRepository;

    public function __construct(TaxonomyRepository $taxonomyRepository, TaxonRepository $taxonRepository)
    {
        $this->taxonRepository = $taxonRepository;
        $this->taxonomyRepository = $taxonomyRepository;
    }

    public function get(Product $product): array
    {
        $taxa = $this->taxonRepository->findMany(array_map(fn (VariantProperty $prop) => $prop->taxonId->get(), $product->getVariantProperties()));

        /** @var Collection<Collection<Taxon>> $groupedByTaxonomy */
        $groupedByTaxonomy = collect($taxa)->groupBy(fn (Taxon $taxon) => $taxon->taxonomyId->get());

        if (count($groupedByTaxonomy) < 2) {
            return [];
        }

        $matrix = $this->createMatrix($groupedByTaxonomy);

        $existingIdCombinations = collect($product->getVariants())
            ->reject(fn (Variant $variant) => count($variant->getVariantProperties()) < 1)
            ->map(fn (Variant $variant) => array_map(fn (\Thinktomorrow\Trader\Domain\Model\Product\VariantTaxa\VariantProperty $prop) => $prop->taxonId->get(), $variant->getVariantProperties()));

        foreach ($matrix as $index => $availableIdCombination) {
            foreach ($existingIdCombinations as $existingIdCombination) {

                if (count(array_diff($availableIdCombination, $existingIdCombination)) === 0) {
                    unset($matrix[$index]);
                }
            }
        }

        // Unfold the matrix
        return $matrix->values()->all();
    }

    public function getAsLabels(Product $product, string $taxonomyLabelKey = 'title.nl', string $taxonLabelKey = 'title.nl'): array
    {
        $missingCombinations = $this->get($product);
        $taxonIds = array_map(fn (VariantProperty $prop) => $prop->taxonId->get(), $product->getVariantProperties());
        $taxa = $this->taxonRepository->findMany($taxonIds);
        $taxonomies = $this->taxonomyRepository->findManyByTaxa($taxonIds);

        foreach ($missingCombinations as $i => $missingCombination) {
            foreach ($missingCombination as $j => $taxonId) {

                /** @var Taxon $taxon */
                $taxon = collect($taxa)
                    ->first(fn (Taxon $taxon) => $taxon->taxonId->get() === $taxonId);

                /** @var Taxonomy $taxonomy */
                $taxonomy = collect($taxonomies)
                    ->first(fn (Taxonomy $taxonomy) => $taxonomy->taxonomyId->get() === $taxon->taxonomyId->get());

                $label = $taxonomy->getData($taxonomyLabelKey);
                $value = $taxon->getData($taxonLabelKey);

                if ($label && $value) {
                    $missingCombinations[$i][$j] = $label . ': ' . $value;
                } elseif ($value) {
                    $missingCombinations[$i][$j] = $value;
                } else {
                    $missingCombinations[$i][$j] = $taxonId;
                }
            }
        }

        return $missingCombinations;
    }

    /**
     * @param Collection $groupedByTaxonomy
     * @return Collection
     */
    private function createMatrix(Collection $groupedByTaxonomy): Collection
    {
        $firstTaxonomyId = $groupedByTaxonomy->keys()->first();

        $matrix = $groupedByTaxonomy->first()->map(fn ($prop) => $prop->taxonId->get());

        foreach ($groupedByTaxonomy as $taxonomyId => $productVariantProperties) {
            if ($firstTaxonomyId === $taxonomyId) {
                continue;
            }

            $matrix = $this->join($matrix, $productVariantProperties->map(fn ($prop) => $prop->taxonId->get())->all());
        }

        return $matrix;
    }

    /**
     * @param \Illuminate\Support\Collection $matrix
     * @return \Illuminate\Support\Collection
     */
    private function join(\Illuminate\Support\Collection $matrix, array $values): \Illuminate\Support\Collection
    {
        $matrix = $matrix->crossJoin($values);

        // After crossjoin it is possible that we should flatten each combo
        foreach ($matrix as $_matrixIndex => $combo) {
            foreach ($combo as $_comboIndex => $item) {
                if (is_array($item)) {
                    unset($combo[$_comboIndex]);
                    $matrix[$_matrixIndex] = array_merge($combo, $item);
                }
            }
        }

        return $matrix;
    }
}
