<?php
declare(strict_types=1);

namespace Thinktomorrow\Trader\Infrastructure\Laravel\config;

use Thinktomorrow\Trader\Domain\Common\Locale;

class TraderConfig implements \Thinktomorrow\Trader\TraderConfig
{
    public function getDefaultLocale(): Locale
    {
        return Locale::fromString(
            config('trader.locale'),
            config('trader.country')
        );
    }

    public function getDefaultCurrency(): string
    {
        return config('trader.currency');
    }

    public function getDefaultTaxRate(): string
    {
        return config('trader.default_tax_rate');
    }

    public function getAvailableTaxRates(): array
    {
        return config('trader.tax_rates');
    }

    public function doesPriceInputIncludesTax(): bool
    {
        return config('trader.does_price_input_includes_tax');
    }
}
