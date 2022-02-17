<?php
declare(strict_types=1);

namespace Thinktomorrow\Trader\Infrastructure\Test;

use Thinktomorrow\Trader\TraderConfig;

class TestTraderConfig implements TraderConfig
{
    public function getDefaultCurrency(): string
    {
        return 'EUR';
    }

    public function getDefaultTaxRate(): string
    {
        return '10';
    }

    public function doesPriceInputIncludesTax(): bool
    {
        return true;
    }
}
