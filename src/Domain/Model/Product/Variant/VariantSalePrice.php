<?php
declare(strict_types=1);

namespace Thinktomorrow\Trader\Domain\Model\Product\Variant;

use Thinktomorrow\Trader\Domain\Common\Cash\Price;
use Thinktomorrow\Trader\Domain\Common\Cash\PriceValue;

final class VariantSalePrice implements Price
{
    use PriceValue;
}
