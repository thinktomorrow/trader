<?php

namespace Thinktomorrow\Trader\Application\VatRate;

use Thinktomorrow\Trader\Domain\Model\VatRate\VatNumber;

interface VatNumberValidator
{
    public function validate(VatNumber $vatNumber): VatNumberValidation;
}
