<?php

return [
    /**
     * Prepends a marker to any exposable references such as cart references, invoice numbers or payment references.
     * This makes it easy to detect any testing identifiers of the application during development or staging.
     */
    'environment-prefix' => env('APP_ENV') == 'production' ? null : env('APP_ENV'),

    /*
     * Currency code that should be used in your application. e.g. EUR, USD, CAD
     * This should be set once in the beginning and never to be touched again
     * to avoid the risk of currency collisions against historical data.
     */
    'currency' => 'EUR',

    /*
     * Default locale following. The default locale is
     * mostly used for rendering the localized Money values
     */
    'locale' => 'nl',

    /*
     * Default country
     * In case the country of the customer or order is not set yet,
     * we use this default country as a fallback for e.g. tax logic
     * Format used is the 2-chars ISO string.
     */
    'country' => 'BE',

    /*
     * Default tax percentage. This is the percentage as integer
     * which is used as a default for all the global taxes
     * such as shipping and payment costs.
     */
    'default_tax_rate' => '21',

    /**
     * All available tax_rates to select from by the webmaster. This is
     */
    'tax_rates' => [
        '21', '6', '12',
    ],

    /**
     * When this value is true, all entered prices as given by the merchant are considered to have
     * tax already included. Set this value to false if all entered prices are always without
     * tax included. Keep in mind that this does not alter already entered price values.
     */
    'does_price_input_includes_vat' => true,

    /**
     * Which taxon subtree represents the main category of the catalog. The main category taxon
     * determines which of the products taxa to use for the breadcrumb tree and structure.
     * If left blank, by default the first taxon subtree found will be used.
     */
    'category_root_id' => null,
];
