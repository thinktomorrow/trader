<?php

declare(strict_types=1);

namespace Thinktomorrow\Trader\Application\Promo\Preview;

enum PromoPreviewStatus
{
    case Irrelevant;
    case Applicable;
    case Conditional;
}
