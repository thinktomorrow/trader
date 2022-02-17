<?php
declare(strict_types=1);

namespace Thinktomorrow\Trader;

interface TraderConfig
{
    public function getDefaultCurrency(): string;

    public function getDefaultTaxRate(): string;

    public function doesPriceInputIncludesTax(): bool;
}
