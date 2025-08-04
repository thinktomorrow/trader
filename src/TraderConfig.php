<?php
declare(strict_types=1);

namespace Thinktomorrow\Trader;

use Thinktomorrow\Trader\Domain\Common\Locale;

interface TraderConfig
{
    public function getEnvironmentPrefix(): ?string;

    public function getDefaultLocale(): Locale;

    public function getDefaultCurrency(): string;

    /**
     * The country that provides the default vat rates.
     * returns a country id like BE, NL, ...
     *
     * This is used to determine the default available vat rates for the catalog.
     * This also determines whether an international business shopper is
     * eligible for vat exemption. Make sure it is a valid country
     * code and that vat rates for this country exist in database.
     */
    public function getPrimaryVatCountry(): string;

    /**
     * Fallback vat rate for when no vat rate is found for the primary country from database
     */
    public function getFallBackStandardVatRate(): string;

    /**
     * When this value is true, all catalog prices as given by the merchant are considered to have
     * tax already included. Set this value to false if all entered prices are always without
     * tax included. Keep in mind that this does not alter already entered price values.
     */
    public function doesPriceInputIncludesVat(): bool;

    /**
     * Do the tariffs set in the admin include vat or not?
     * This is mainly for the shipping tariffs.
     *
     * @return bool
     */
    public function doesTariffInputIncludesVat(): bool;

    /**
     * Prices will be calculated including or excluding vat. This makes sure that calculations are correct
     * and don't cause any rounding errors - which could occur when calculating excluding vat and
     * including the vat afterwards. this can be set according to the visitor demands (b2b or b2c).
     *
     * @return bool
     */
    public function includeVatInPrices(): bool;

    /**
     * If this is true, the shop allows vat exemption for international business shoppers.
     * This means that if a shopper is a business and has a valid vat number, they are
     * eligible for vat exemption if their country is different from the primary vat country.
     */
    public function isVatExemptionAllowed(): bool;

    public function getMainCategoryTaxonomyId(): ?string;

    public function getClassMap(): array;

    public function getWebmasterEmail(): string;

    public function getWebmasterName(): string;
}
