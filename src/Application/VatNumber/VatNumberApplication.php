<?php
declare(strict_types=1);

namespace Thinktomorrow\Trader\Application\VatNumber;

use Thinktomorrow\Trader\Domain\Model\Order\Shopper;
use Thinktomorrow\Trader\Domain\Model\VatNumber\Exceptions\InvalidVatNumber;
use Thinktomorrow\Trader\Domain\Model\VatNumber\Exceptions\VatNumberCountryMismatch;
use Thinktomorrow\Trader\Domain\Model\VatNumber\VatNumber;

class VatNumberApplication
{
    private VatNumberValidator $validator;

    public function __construct(VatNumberValidator $validator)
    {
        $this->validator = $validator;
    }

    public function addVatNumberValidationToShopper(Shopper $shopper, VatNumberValidation $vatNumberValidation): void
    {
        $shopper->addData([
            'vat_number' => $vatNumberValidation->vatNumber,
            'vat_number_validation_timestamp' => time(),
            'vat_number_valid' => $vatNumberValidation->isValid(),
            'vat_number_state' => $vatNumberValidation->state->value,
            'vat_number_country' => $vatNumberValidation->countryCode,
            'vat_number_validation_error' => $vatNumberValidation->getError(),
        ]);
    }

    public function validate(ValidateVatNumber $command): VatNumberValidation
    {
        try {
            return $this->validator->validate(
                VatNumber::make($command->getCountryId(), $command->getVatNumber())
            );
        } catch (InvalidVatNumber $e) {
            return VatNumberValidation::fromInvalidVatFormat($command->getCountryId()->get(), $command->getVatNumber(), $e);
        } catch (VatNumberCountryMismatch $e) {
            return VatNumberValidation::fromVatNumberCountryMismatch($command->getCountryId()->get(), $command->getVatNumber(), $e);
        }
    }
}
