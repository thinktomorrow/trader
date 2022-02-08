<?php
declare(strict_types=1);

namespace Thinktomorrow\Trader\Catalog\Options\Application;

use Illuminate\Support\Facades\DB;

class CheckExistingOptionCombination
{
    public function exists(array $optionIds, $excludeProductId = null): bool
    {
        $optionIdPlaceholders = implode(',', array_fill(0, count($optionIds), '?'));
        $bindings = $this->prepareBindings($optionIds, $excludeProductId);

        if (count($optionIds) < 2) {
            $statement = "SELECT A.product_id, count(distinct A.option_id) AS count FROM trader_option_product A WHERE A.option_id = $optionIdPlaceholders";
            $statement .= ($excludeProductId ? ' AND A.product_id <> ?' : '');
        } else {
            $statement = "SELECT A.product_id, count(distinct A.option_id) AS count FROM trader_option_product A INNER JOIN trader_option_product B ON A.product_id = B.product_id AND A.option_id IN ($optionIdPlaceholders) AND B.option_id IN ($optionIdPlaceholders) AND A.option_id <> B.option_id";
            $statement .= ($excludeProductId ? ' WHERE A.product_id <> ?' : '');
        }

        $statement .= ' GROUP BY A.product_id';

        $result = DB::select($statement, $bindings);

        foreach ($result as $row) {
            if ($row->count === count($optionIds)) {
                return true;
            }
        }

        return false;
    }

    private function prepareBindings(array $optionIds, $excludeProductId = null): array
    {
        // Duplicate the bindings per group since each group will be set twice.
        $bindings = count($optionIds) > 1 ? array_merge($optionIds, $optionIds) : $optionIds;

        $bindings = $excludeProductId ? array_merge($bindings, [$excludeProductId]) : $bindings;

        return array_values($bindings);
    }
}
