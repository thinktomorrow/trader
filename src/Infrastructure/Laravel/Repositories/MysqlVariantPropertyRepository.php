<?php
declare(strict_types=1);

namespace Thinktomorrow\Trader\Infrastructure\Laravel\Repositories;

use Illuminate\Support\Facades\DB;
use Thinktomorrow\Trader\Application\Product\VariantProperties\VariantPropertyRepository;

class MysqlVariantPropertyRepository implements VariantPropertyRepository
{
    public function doesUniqueVariantPropertyCombinationExist(string $productId, array $taxonIds, ?string $excludeVariantId = null): bool
    {
        // No duplicate values
        $taxonIds = array_values(array_unique($taxonIds));

        if (empty($taxonIds)) {
            return false;
        }

        $n = count($taxonIds);
        $inPlaceholders = implode(',', array_fill(0, $n, '?'));

        $sql = "
            SELECT v.variant_id AS variant_id
            FROM trader_product_variants v
            JOIN trader_taxa_variants t ON t.variant_id = v.variant_id
            WHERE v.product_id = ?
            " . ($excludeVariantId ? "AND v.variant_id <> ?\n" : "") . "
            GROUP BY v.variant_id
            HAVING
                -- variant heeft precies n taxa (dus geen extra's)
                COUNT(DISTINCT t.taxon_id) = {$n}
                -- en die n taxa vallen exact binnen de opgegeven set
                AND COUNT(DISTINCT CASE WHEN t.taxon_id IN ($inPlaceholders) THEN t.taxon_id END) = {$n}
            LIMIT 1
        ";

        $bindings = $this->composeBindings($productId, $excludeVariantId, $taxonIds);

        $rows = DB::select($sql, $bindings);

        return ! empty($rows);
    }

    //    private function prepareBindings(array $taxonIds, ?string $excludeVariantId = null): array
    //    {
    //        // Duplicate the bindings per group since each group will be set twice.
    //        $bindings = count($taxonIds) > 1 ? array_merge($taxonIds, $taxonIds) : $taxonIds;
    //
    //        $bindings = $excludeVariantId ? array_merge($bindings, [$excludeVariantId]) : $bindings;
    //
    //        return array_values($bindings);
    //    }

    /**
     * @param string $productId
     * @param string|null $excludeVariantId
     * @param array $taxonIds
     * @return array|string[]
     */
    public function composeBindings(string $productId, ?string $excludeVariantId, array $taxonIds): array
    {
        $bindings = [$productId];

        if ($excludeVariantId) {
            $bindings[] = $excludeVariantId;
        }

        $bindings = array_merge($bindings, $taxonIds);

        return $bindings;
    }
}
