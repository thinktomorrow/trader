<?php
declare(strict_types=1);

namespace Thinktomorrow\Trader;

use Thinktomorrow\Trader\Domain\Common\Locale;

interface TraderConfig
{
    public function getDefaultLocale(): Locale;

    public function getDefaultCurrency(): string;

    public function getDefaultTaxRate(): string;

    public function doesPriceInputIncludesTax(): bool;
}
