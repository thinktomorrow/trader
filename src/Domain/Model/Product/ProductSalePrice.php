<?php
declare(strict_types=1);

namespace Thinktomorrow\Trader\Domain\Model\Product;

use Thinktomorrow\Trader\Domain\Common\Cash\Price;
use Thinktomorrow\Trader\Domain\Common\Cash\PriceValue;

final class ProductSalePrice implements Price
{
    use PriceValue;
}
