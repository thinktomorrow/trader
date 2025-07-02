<?php

namespace Thinktomorrow\Trader\Infrastructure\Test;

use Thinktomorrow\Trader\Application\VatNumber\VatNumberValidation;
use Thinktomorrow\Trader\Application\VatNumber\VatNumberValidator;
use Thinktomorrow\Trader\Domain\Model\VatNumber\VatNumber;
use Thinktomorrow\Trader\Domain\Model\VatNumber\VatNumberValidationState;

class DummyVatNumberValidator implements VatNumberValidator
{
    private ?VatNumberValidation $expectedResult = null;

    public function __construct()
    {
    }

    public function validate(VatNumber $vatNumber): VatNumberValidation
    {
        if ($this->expectedResult) {
            return $this->expectedResult;
        }

        return new VatNumberValidation($vatNumber->getCountryCode(), $vatNumber->getNumber(), VatNumberValidationState::valid, [

        ]);
    }

    public function setExpectedResult(VatNumberValidation $vatNumberValidation)
    {
        $this->expectedResult = $vatNumberValidation;
    }
}
