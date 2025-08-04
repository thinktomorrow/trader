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

    /**
     * The country that provides the default vat rates.
     * returns a country id like BE, NL, ...
     *
     * This is used to determine the default available vat rates for the catalog.
     * Make sure it is a valid country code and that vat rates for this country exist in database.
     */
    public function getPrimaryVatCountry(): string
    {
        return config('trader.primary_vat_country');
    }

    public function getFallBackStandardVatRate(): string
    {
        return config('trader.fallback_standard_vat_rate');
    }

    /**
     * @deprecated Use getPrimaryVatRate instead
     */
    public function getDefaultTaxRate(): string
    {
        throw new \Exception('Deprecated method getDefaultTaxRate. Use getPrimaryVatRate instead.');
    }

    /**
     * @deprecated Use getPrimaryVatCountry instead to fetch all available tax rates.
     */
    public function getAvailableTaxRates(): array
    {
        throw new \Exception('Deprecated method getAvailableTaxRates. Use getPrimaryVatCountry instead to fetch all available tax rates.');
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

    public function isVatExemptionAllowed(): bool
    {
        return config('trader.allow_vat_exemption', true);
    }

    public function getMainCategoryTaxonomyId(): ?string
    {
        return config('trader.category_taxonomy_id');
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
