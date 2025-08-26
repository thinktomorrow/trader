<?php
declare(strict_types=1);

namespace Thinktomorrow\Trader\Application\Product\VariantPropertyCombination;

use Illuminate\Support\Collection;
use Thinktomorrow\Trader\Application\Product\VariantLinks\ProductOptionsAndValues;
use Thinktomorrow\Trader\Domain\Model\Product\Product;
use Thinktomorrow\Trader\Domain\Model\Product\Variant\Variant;
use Thinktomorrow\Trader\Domain\Model\Product\VariantTaxa\ProductVariantProperty;
use Thinktomorrow\Trader\Domain\Model\Taxon\TaxonId;

class MissingVariantPropertyCombinations
{
    private ProductOptionsAndValues $productOptionValues;

    public function __construct(ProductOptionsAndValues $productOptionValues)
    {
        $this->productOptionValues = $productOptionValues;
    }

    public function getAsLabels(Product $product, string $optionLabelKey, string $optionValueLabelKey): array
    {
        $productVariantProperties = $product->getProductVariantProperties();
        dd($productVariantProperties, $this->productTaxonRepository->getTaxaByProduct(
            array_map(fn ($prop) => $prop->taxonId->get(), $productVariantProperties),
        ));
        //        $options = $this->productOptionValues->get($product->productId->get());

        dd($optionLabelKey, $optionValueLabelKey);
        $missingCombinations = $this->get($product);

        foreach ($missingCombinations as $i => $missingCombination) {
            foreach ($missingCombination as $j => $taxonId) {
                $missingCombinations[$i][$j] = $this->findOptionLabelByValue($product, $taxonId, $optionLabelKey, $optionValueLabelKey);
            }
        }

        return $missingCombinations;
    }

    private function findOptionLabelByValue(Product $product, $option_value_id, string $optionLabelKey, string $optionValueLabelKey): ?string
    {
        foreach ($product->getOptions() as $option) {
            foreach ($option->getOptionValues() as $optionValue) {
                if ($option_value_id === $optionValue->optionValueId->get()) {
                    $optionLabel = $option->getData($optionLabelKey);
                    $optionValueLabel = $optionValue->getData($optionValueLabelKey);

                    if (! is_string($optionLabel) || ! is_string($optionValueLabel)) {
                        return null;
                    }

                    return $optionLabel . ': ' . $optionValueLabel;
                }
            }
        }

        return null;
    }

    public function get(Product $product): array
    {
        $productVariantProperties = $product->getProductVariantProperties();
        //        $options = $this->productOptionValues->get($product->productId->get());

        /** @var Collection<ProductVariantProperty> $groupedByTaxonomy */
        $groupedByTaxonomy = collect($product->getProductVariantProperties())->groupBy(fn (ProductVariantProperty $val) => $val->taxonomyId->get());

        //        foreach ($productVariantProperties as $productVariantProperty) {
        //            $groupedByTaxonomy[] = array_map(fn(ProductVariantProperty $val) => $val->taxonId, $productVariantProperties);
        //        }

        if (count($groupedByTaxonomy) < 2) {
            return [];
        }

        $matrix = $this->createMatrix($groupedByTaxonomy);

        $existingIdCombinations = collect($product->getVariants())
            ->reject(fn (Variant $variant) => count($variant->getVariantTaxonIds()) < 1)
            ->map(fn (Variant $variant) => array_map(fn (TaxonId $taxonId) => $taxonId->get(), $variant->getVariantTaxonIds()));

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
