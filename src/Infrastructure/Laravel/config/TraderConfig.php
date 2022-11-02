<?php
declare(strict_types=1);

namespace Thinktomorrow\Trader\Infrastructure\Laravel\config;

use Thinktomorrow\Trader\Domain\Common\Locale;

class TraderConfig implements \Thinktomorrow\Trader\TraderConfig
{
    public function getEnvironmentPrefix(): ?string
    {
        return config('trader.environment-prefix');
    }

    public function getDefaultLocale(): Locale
    {
        return Locale::fromString(
            app()->getLocale() ?: config('trader.locale')
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

    public function doesPriceInputIncludesVat(): bool
    {
        return config('trader.does_price_input_includes_vat');
    }

    public function doesTariffInputIncludesVat(): bool
    {
        return config('trader.does_tariff_input_includes_vat');
    }

    public function includeVatInPrices(): bool
    {
        return config('trader.include_vat_in_prices');
    }

    public function getCategoryRootId(): ?string
    {
        return config('trader.category_root_id');
    }

    public function getClassMap(): array
    {
        return config('trader.classmap', []);
    }

    public function getWebmasterEmail(): string
    {
        return config('trader.webmaster_email');
    }

    public function getWebmasterName(): string
    {
        return config('trader.webmaster_name');
    }
}
