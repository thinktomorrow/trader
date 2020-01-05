<?php

return [

    /**
     * Prepends a marker to any exposable references such as cart references, invoice numbers or payment references.
     * This makes it easy to detect any testing identifiers outside of the application.
     */
    'environment-prefix' => 'local',

    /*
     * Currency code that should be used in your application. e.g. EUR, USD, CAD
     * This should be set once in the beginning and never to be touched again
     * to avoid the risk of currency collisions against historical data.
     */
    'currency' => 'EUR',

    /*
     * Default locale following the IETF locale format e.g. en-US where
     * en is the language and US the region. The default locale is
     * mostly used for rendering the localized Money values
     */
    'locale' => 'nl-BE',

    /*
     * Default country
     * In case the country of the customer or order is not set yet,
     * we use this default country as a fallback for e.g. tax logic
     * Format used is the 2-chars ISO string.
     */
    'country_id' => 'BE',

    /*
     * Default tax percentage. This is the percentage as integer
     * which is used as a default for all the global taxes
     * such as shipping and payment costs.
     */
    'tax_percentage' => 21,

];
