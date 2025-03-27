<?php

namespace Thinktomorrow\Trader\Infrastructure\Vies;

use SoapClient;

class ViesClient
{
    private string $wsdl = "http://ec.europa.eu/taxation_customs/vies/checkVatService.wsdl";

    private SoapClient $client;

    public function __construct(array $config)
    {
        $this->client = new SoapClient($this->wsdl, $config);
    }

    /**
     * Returns an object with something like:
     *  {
     *  "countryCode": "BE"
     *  "vatNumber": "0412192313"
     *  "requestDate": "2025-03-27 +01:00"
     *  "valid": false
     *  "name": "---"
     *  "address": "---"
     * }
     */
    public function check(string $countryCode, string $vatNumber): object
    {
        return $this->client->checkVat([
            'countryCode' => $countryCode,
            'vatNumber' => $vatNumber,
        ]);
    }

    public static function createDefault(): self
    {
        return new static([
            'uri' => 'http://schemas.xmlsoap.org/soap/envelope/',
            'style' => SOAP_RPC,
            'use' => SOAP_ENCODED,
            'soap_version' => SOAP_1_1,
            'cache_wsdl' => WSDL_CACHE_BOTH,
            'connection_timeout' => 15,
            'trace' => true,
            'encoding' => 'UTF-8',
            'exceptions' => true,
            'features' => SOAP_SINGLE_ELEMENT_ARRAYS,
            'compression' => SOAP_COMPRESSION_ACCEPT | SOAP_COMPRESSION_GZIP,
        ]);
    }
}
