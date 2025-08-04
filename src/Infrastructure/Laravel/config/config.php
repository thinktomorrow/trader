<?php

return [
    /**
     * Prepends a marker to any exposable references such as cart references, invoice numbers or payment references.
     * This makes it easy to detect any testing identifiers of the application during development or staging.
     */
    'environment-prefix' => env('APP_ENV') == 'production' ? null : env('APP_ENV'),

    /**
     * Currency code that should be used in your application. e.g. EUR, USD, CAD
     * This should be set once in the beginning and never to be touched again
     * to avoid the risk of currency collisions against historical data.
     */
    'currency' => 'EUR',

    /**
     * Default locale following the ISO 639-1 standard. The default locale is
     * mostly used for rendering the localized Money values.
     */
    'locale' => 'nl-be',

    /**
     * The country that provides the default vat rates. e.g. BE, NL, ...
     *
     * This is used to determine the available vat rates for the catalog admin. Also, in case the country
     * of the customer or order is not set yet, we use this default country as a fallback for
     * e.g. vat logic. Make sure it is a valid country code and that at least the standard
     * vat rate for this country exists in database.
     */
    'primary_vat_country' => 'BE',

    /**
     * Fallback vat rate for when no standard vat rate can
     * be found for the primary country from database
     */
    'fallback_standard_vat_rate' => '21',

    /**
     * When this value is true, all catalog prices as given by the merchant are considered to have
     * tax already included. Set this value to false if all entered prices are always without
     * tax included. Keep in mind that this does not alter already entered price values.
     */
    'does_price_input_includes_vat' => true,

    /**
     * Do the tariffs set in the admin include vat or not?
     * This is mainly for the shipping tariffs.
     */
    'does_tariff_input_includes_vat' => true,

    /**
     * Prices on the shop will be calculated including or excluding vat. This makes sure that calculations are correct
     * and don't cause any rounding errors - which could occur when calculating excluding vat and including the vat
     * afterward. This can be set according to the visitor demands (b2b or b2c). This also determines how the
     * catalog prices are displayed.
     */
    'include_vat_in_prices' => true,

    /**
     * If this is true, the shop allows vat exemption for international business shoppers.
     * This means that if a shopper is a business and has a valid vat number, they are
     * eligible for vat exemption if their country is different from the primary vat country.
     */
    'allow_vat_exemption' => true,

    /**
     * Which taxon subtree represents the main category of the catalog. The main category taxon
     * determines which of the products taxa to use for the breadcrumb tree and structure.
     * If left blank, by default the first taxon subtree found will be used.
     */
    'category_taxonomy_id' => null,

    /**
     * The mail address that will be used to send
     * the customer password reset mail
     */
    'webmaster_email' => env('MAIL_FROM_ADDRESS'),

    /**
     * The name that recipients will see as the sender
     * when receiving the password reset mail
     */
    'webmaster_name' => env('MAIL_FROM_NAME'),
];
