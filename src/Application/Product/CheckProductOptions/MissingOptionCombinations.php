<?php
declare(strict_types=1);

namespace Thinktomorrow\Trader\Application\Product\CheckProductOptions;

use Thinktomorrow\Trader\Application\Product\OptionLinks\ProductOptionValues;
use Thinktomorrow\Trader\Domain\Model\Product\Product;
use Thinktomorrow\Trader\Domain\Model\Product\Variant\Variant;

class MissingOptionCombinations
{
    private ProductOptionValues $productOptionValues;

    public function __construct(ProductOptionValues $productOptionValues)
    {
        $this->productOptionValues = $productOptionValues;
    }

    public function getAsLabels(Product $product, string $optionLabelKey, string $optionValueLabelKey): array
    {
        $options = $this->productOptionValues->get($product->productId->get());

        $missingCombinations = $this->get($product);

        foreach ($missingCombinations as $i => $missingCombination) {
            foreach ($missingCombination as $j => $option_value_id) {
                $missingCombinations[$i][$j] = $this->findOptionLabelByValue($product, $option_value_id, $optionLabelKey, $optionValueLabelKey);
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
                        trap($optionLabel, $optionValueLabel);

                        return null;
                    }

                    return $optionLabel .': ' . $optionValueLabel;
                }
            }
        }

        return null;
    }

    public function get(Product $product): array
    {
        $options = $this->productOptionValues->get($product->productId->get());

        $optionValuesGrouped = [];
        foreach ($options as $option) {
            $optionValuesGrouped[] = array_map(fn ($value) => $value['option_value_id'], $option['values']);
        }

        if (count($optionValuesGrouped) < 2) {
            return [];
        }

        $matrix = collect($optionValuesGrouped[0]);
        for ($i = 1;$i < count($optionValuesGrouped);$i++) {
            $matrix = $this->join($matrix, $optionValuesGrouped[$i]);
        }

        $existingIdCombinations = collect($product->getVariants())
            ->reject(fn (Variant $variant) => count($variant->getMappedData()['option_value_ids']) < 1)
            ->map(fn (Variant $variant) => $variant->getMappedData()['option_value_ids']);

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
     * @param \Illuminate\Support\Collection $matrix
     * @return \Illuminate\Support\Collection
     */
    private function join(\Illuminate\Support\Collection $matrix, array $options): \Illuminate\Support\Collection
    {
        $matrix = $matrix->crossJoin($options);

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
