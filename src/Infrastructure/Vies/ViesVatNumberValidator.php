<?php

namespace Thinktomorrow\Trader\Infrastructure\Vies;

use SoapFault;
use Thinktomorrow\Trader\Application\VatNumber\VatNumberValidation;
use Thinktomorrow\Trader\Application\VatNumber\VatNumberValidator;
use Thinktomorrow\Trader\Domain\Model\VatNumber\VatNumber;
use Thinktomorrow\Trader\Domain\Model\VatNumber\VatNumberValidationState;

class ViesVatNumberValidator implements VatNumberValidator
{
    public function __construct(private ViesClient $client)
    {
    }

    /**
     * Common SoapFaults (taken from https://github.com/DragonBe/vies/blob/master/src/Vies/Vies.php) include:
     *
     * MS_UNAVAILABLE            : The Member State service is unavailable. Try again later or with another Member State.
     * SERVER_BUSY               : The service can not process your request. Try again later.
     * SERVICE_UNAVAILABLE       : The SOAP service is unavailable, try again later.
     * TIMEOUT                   : The Member State service could not be reach in time, try again later or with another
     *                             Member State
     * INVALID_INPUT             : FAULTY INPUT
     *
     * GLOBAL_MAX_CONCURRENT_REQ : The number of concurrent requests is more than the VIES service allows.
     * MS_MAX_CONCURRENT_REQ     : Same as MS_MAX_CONCURRENT_REQ.
     */
    public function validate(VatNumber $vatNumber): VatNumberValidation
    {
        try {
            $result = $this->client->check($vatNumber->getCountryCode(), $vatNumber->getNumber());

            $state = $result->valid ? VatNumberValidationState::valid : VatNumberValidationState::invalid;

            return new VatNumberValidation($vatNumber->getCountryCode(), $vatNumber->getNumber(), $state, []);
        } catch (SoapFault $e) {

            // Faulty input
            if ($e->getMessage() == "INVALID_INPUT") {
                return new VatNumberValidation($vatNumber->getCountryCode(), $vatNumber->getNumber(), VatNumberValidationState::invalid, [
                    'error' => 'Invalid VAT number',
                ]);
            }

            return new VatNumberValidation($vatNumber->getCountryCode(), $vatNumber->getNumber(), VatNumberValidationState::service_error, [
                'error' => 'VIES service is currently unavailable. Reason: [' . $e->getMessage() . ']',
            ]);
        }
    }
}
