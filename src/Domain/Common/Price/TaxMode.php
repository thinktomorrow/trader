<?php

declare(strict_types=1);

namespace Thinktomorrow\Trader\Domain\Common\Price;

enum TaxMode: string
{
    case Inclusive = 'inclusive';
    case Exclusive = 'exclusive';
}
