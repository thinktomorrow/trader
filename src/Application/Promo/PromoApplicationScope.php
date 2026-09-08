<?php

declare(strict_types=1);

namespace Thinktomorrow\Trader\Application\Promo;

enum PromoApplicationScope
{
    case All;
    case Lines;
    case ServicesAndOrder;
}
