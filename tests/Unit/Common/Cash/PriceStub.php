<?php
declare(strict_types=1);

namespace Tests\Unit\Common\Cash;

use Thinktomorrow\Trader\Domain\Common\Price\Old\Price;
use Thinktomorrow\Trader\Domain\Common\Price\Old\PriceValue;

final class PriceStub implements Price
{
    use PriceValue;
}
