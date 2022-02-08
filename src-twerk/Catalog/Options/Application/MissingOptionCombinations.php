<?php
declare(strict_types=1);

namespace Thinktomorrow\Trader\Catalog\Options\Application;

use function collect;
use Thinktomorrow\Trader\Catalog\Options\Ports\Options;
use Thinktomorrow\Trader\Catalog\Products\Domain\ProductGroup;

final class MissingOptionCombinations
{
    public function scan(ProductGroup $productGroup): array
    {
        $grouped = $productGroup->getOptions()->grouped();

        if (count($grouped) < 2) {
            return [];
        }

        $matrix = collect($grouped[0]);
        for ($i = 1;$i < count($grouped);$i++) {
            $matrix = $this->join($matrix, $grouped[$i]);
        }

        $existingIdCombinations = $productGroup->getProducts()
            ->reject(fn ($product) => $product->getOptions()->count() < 1)
            ->map(function ($product) {
                return $product->getOptions()->getIds();
            });

        foreach ($matrix as $index => $availableIdCombinations) {
            foreach ($existingIdCombinations as $existingIdCombination) {
                if (count(array_diff((new Options(...$availableIdCombinations))->getIds(), $existingIdCombination)) === 0) {
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
