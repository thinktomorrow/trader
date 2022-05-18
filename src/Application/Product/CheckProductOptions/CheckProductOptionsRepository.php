<?php

namespace Thinktomorrow\Trader\Application\Product\CheckProductOptions;

interface CheckProductOptionsRepository
{
    public function exists(array $option_value_ids, $excluded_variant_id = null): bool;
}
