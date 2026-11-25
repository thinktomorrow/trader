<?php

declare(strict_types=1);

namespace Thinktomorrow\Trader\Application\Promo\Preview;

enum PromoPreviewStatus
{
    case Irrelevant;

    /** The cart already meets the conditions; this does not mean a discount was applied. */
    case AlreadyEligible;
    case Applicable;
    case Conditional;
}
