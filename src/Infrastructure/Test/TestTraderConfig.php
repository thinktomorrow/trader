<?php
declare(strict_types=1);

namespace Thinktomorrow\Trader\Infrastructure\Test;

use Thinktomorrow\Trader\TraderConfig;
use Thinktomorrow\Trader\Domain\Common\Locale;

class TestTraderConfig implements TraderConfig
{
    public function getDefaultLocale(): Locale
    {
        return Locale::fromString('nl', 'BE');
    }

    public function getDefaultCurrency(): string
    {
        return 'EUR';
    }

    public function getDefaultTaxRate(): string
    {
        return '10';
    }

    public function getAvailableTaxRates(): array
    {
        return ['21', '12', '6', '10'];
    }

    public function doesPriceInputIncludesTax(): bool
    {
        return true;
    }
}
