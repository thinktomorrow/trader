<?php
declare(strict_types=1);

namespace Thinktomorrow\Trader\Infrastructure\Laravel\Repositories;

use Illuminate\Support\Facades\DB;
use Thinktomorrow\Trader\Application\Product\VariantPropertyCombination\VariantPropertyRepository;

class MysqlVariantPropertyRepository implements VariantPropertyRepository
{
    public function doesUniqueVariantPropertyCombinationExist(array $taxonIds, ?string $excludeVariantId = null): bool
    {
        $taxonIdPlaceholders = implode(',', array_fill(0, count($taxonIds), '?'));
        $bindings = $this->prepareBindings($taxonIds, $excludeVariantId);

        if (count($taxonIds) < 2) {
            $statement = "SELECT A.variant_id, count(distinct A.taxon_id) AS count FROM trader_taxa_variants A WHERE A.taxon_id = $taxonIdPlaceholders";
            $statement .= ($excludeVariantId ? ' AND A.variant_id <> ?' : '');
        } else {
            $statement = "SELECT A.variant_id, count(distinct A.taxon_id) AS count FROM trader_taxa_variants A INNER JOIN trader_taxa_variants B ON A.variant_id = B.variant_id AND A.taxon_id IN ($taxonIdPlaceholders) AND B.taxon_id IN ($taxonIdPlaceholders) AND A.taxon_id <> B.taxon_id";
            $statement .= ($excludeVariantId ? ' WHERE A.variant_id <> ?' : '');
        }

        $statement .= ' GROUP BY A.variant_id';

        $result = DB::select($statement, $bindings);

        foreach ($result as $row) {
            if ($row->count === count($taxonIds)) {
                return true;
            }
        }

        return false;
    }

    private function prepareBindings(array $taxonIds, ?string $excludeVariantId = null): array
    {
        // Duplicate the bindings per group since each group will be set twice.
        $bindings = count($taxonIds) > 1 ? array_merge($taxonIds, $taxonIds) : $taxonIds;

        $bindings = $excludeVariantId ? array_merge($bindings, [$excludeVariantId]) : $bindings;

        return array_values($bindings);
    }
}
