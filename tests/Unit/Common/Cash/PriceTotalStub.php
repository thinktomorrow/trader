<?php
declare(strict_types=1);

namespace Tests\Unit\Common\Cash;

use Thinktomorrow\Trader\Domain\Common\Price\PriceTotal;
use Thinktomorrow\Trader\Domain\Common\Price\PriceTotalValue;

final class PriceTotalStub implements PriceTotal
{
    use PriceTotalValue;
}
