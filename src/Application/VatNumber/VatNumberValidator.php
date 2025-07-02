<?php

namespace Thinktomorrow\Trader\Application\VatNumber;

use Thinktomorrow\Trader\Domain\Model\VatNumber\VatNumber;

interface VatNumberValidator
{
    public function validate(VatNumber $vatNumber): VatNumberValidation;
}
