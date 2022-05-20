<?php
declare(strict_types=1);

namespace Thinktomorrow\Trader\Infrastructure\Laravel\config;

use Thinktomorrow\Trader\Domain\Common\Locale;

class TraderConfig implements \Thinktomorrow\Trader\TraderConfig
{
    public function getDefaultLocale(): Locale
    {
        return Locale::fromString(
            config('trader.language'),
            config('trader.country')
        );
    }

    public function getDefaultRegion(): string
    {
        return config('trader.country');
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

    public function doesPriceInputIncludesVat(): bool
    {
        return config('trader.does_price_input_includes_vat');
    }

    public function getCategoryRootId(): ?string
    {
        return config('trader.category_root_id');
    }

    public function getClassMap(): array
    {
        return config('trader.classmap', []);
    }
}
