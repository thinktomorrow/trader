<?php

declare(strict_types=1);

namespace Thinktomorrow\Trader\Application\Promo\Preview;

enum PromoPreviewStatus
{
    case Irrelevant;

    /** The conditions were met before adding the previewed product; this does not mean a discount was applied. */
    case AlreadyEligible;
    case Applicable;
    case Conditional;
}
