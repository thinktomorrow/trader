<?php
declare(strict_types=1);

namespace Tests\Unit\Common\Cash;

use Thinktomorrow\Trader\Domain\Common\Cash\Price;
use Thinktomorrow\Trader\Domain\Common\Cash\PriceValue;

final class PriceStub implements Price
{
    use PriceValue;
}
