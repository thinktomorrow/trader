<?php
declare(strict_types=1);

namespace Thinktomorrow\Trader\Infrastructure\Test;

use Thinktomorrow\Trader\Domain\Common\Locale;
use Thinktomorrow\Trader\TraderConfig;

class TestTraderConfig implements TraderConfig
{
    private array $overwrites;

    private static ?string $mainCategoryTaxonomyId = null;

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

    public function getFallBackStandardVatRate(): string
    {
        return '21';
    }

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

    public function isVatExemptionAllowed(): bool
    {
        return $this->overwrites['allow_vat_exemption'] ?? true;
    }

    public function getMainCategoryTaxonomyId(): ?string
    {
        if (self::$mainCategoryTaxonomyId) {
            return self::$mainCategoryTaxonomyId;
        }

        return $this->overwrites['category_taxonomy_id'] ?? null;
    }

    public static function setMainCategoryTaxonomyId(string $taxonomyId): void
    {
        self::$mainCategoryTaxonomyId = $taxonomyId;
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

    public static function clear(): void
    {
        self::$mainCategoryTaxonomyId = null;
    }
}
