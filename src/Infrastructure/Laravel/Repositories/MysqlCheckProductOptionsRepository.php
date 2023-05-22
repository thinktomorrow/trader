<?php
declare(strict_types=1);

namespace Thinktomorrow\Trader\Infrastructure\Laravel\Repositories;

use Illuminate\Support\Facades\DB;
use Thinktomorrow\Trader\Application\Product\CheckProductOptions\CheckProductOptionsRepository;

class MysqlCheckProductOptionsRepository implements CheckProductOptionsRepository
{
    //    private static string $optionTable = 'trader_product_options';
    //    private static string $optionValueTable = 'trader_product_option_values';
    private static string $variantOptionValueLookupTable = 'trader_variant_option_values';

    public function exists(array $option_value_ids, $excluded_variant_id = null): bool
    {
        // Make sure we don't have an assoc list because this would screw up our bindings
        $option_value_ids = array_values($option_value_ids);

        $optionIdPlaceholders = implode(',', array_fill(0, count($option_value_ids), '?'));
        $bindings = $this->prepareBindings($option_value_ids, $excluded_variant_id);

        if (count($option_value_ids) < 2) {
            $statement = "SELECT A.variant_id, count(distinct A.option_value_id) AS count FROM ".static::$variantOptionValueLookupTable." A WHERE A.option_value_id = $optionIdPlaceholders";
            $statement .= ($excluded_variant_id ? ' AND A.variant_id <> ?' : '');
        } else {
            $statement = "SELECT A.variant_id, count(distinct A.option_value_id) AS count FROM ".static::$variantOptionValueLookupTable." A INNER JOIN ".static::$variantOptionValueLookupTable." B ON A.variant_id = B.variant_id AND A.option_value_id IN ($optionIdPlaceholders) AND B.option_value_id IN ($optionIdPlaceholders) AND A.option_value_id <> B.option_value_id";
            $statement .= ($excluded_variant_id ? ' WHERE A.variant_id <> ?' : '');
        }

        $statement .= ' GROUP BY A.variant_id';

        $result = DB::select($statement, $bindings);

        foreach ($result as $row) {
            if ($row->count === count($option_value_ids)) {
                return true;
            }
        }

        return false;
    }

    private function prepareBindings(array $option_value_ids, $excluded_variant_id = null): array
    {
        // Duplicate the bindings per group since each group will be set twice.
        $bindings = count($option_value_ids) > 1 ? array_merge($option_value_ids, $option_value_ids) : $option_value_ids;

        $bindings = $excluded_variant_id ? array_merge($bindings, [$excluded_variant_id]) : $bindings;

        return array_values($bindings);
    }
}
