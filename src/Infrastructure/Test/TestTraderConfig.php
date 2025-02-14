<?php
declare(strict_types=1);

namespace Thinktomorrow\Trader\Infrastructure\Test;

use Thinktomorrow\Trader\Domain\Common\Locale;
use Thinktomorrow\Trader\TraderConfig;

class TestTraderConfig implements TraderConfig
{
    private array $overwrites;

    public function __construct(array $overwrites = [])
    {
        $this->overwrites = $overwrites;
    }

    public function getEnvironmentPrefix(): ?string
    {
        return 'test-';
    }

    public function getDefaultLocale(): Locale
    {
        return Locale::fromString('nl');
    }

    public function getDefaultCurrency(): string
    {
        return 'EUR';
    }

    /**
     * The default vat rate for the catalog. This is typically
     * the standard rate of the primary VAT country.
     */
    public function getPrimaryVatRate(): string
    {
        return '10';
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
        return 'BE';
    }


//    public function getDefaultTaxRate(): string
//    {
//        return '10';
//    }
//
//    public function getAvailableTaxRates(): array
//    {
//        return ['21', '12', '6', '10'];
//    }

    public function doesPriceInputIncludesVat(): bool
    {
        return true;
    }

    public function doesTariffInputIncludesVat(): bool
    {
        return true;
    }

    public function includeVatInPrices(): bool
    {
        return true;
    }

    public function getCategoryRootId(): ?string
    {
        return $this->overwrites['category_root_id'] ?? null;
    }

    public function getClassMap(): array
    {
        return [];
    }

    public function getWebmasterEmail(): string
    {
        return 'dev@trader.be';
    }

    public function getWebmasterName(): string
    {
        return 'ben';
    }
}
