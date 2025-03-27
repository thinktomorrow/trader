<?php
declare(strict_types=1);

namespace Thinktomorrow\Trader\Application\VatRate;

class VatNumberApplication
{
    private VatNumberValidator $validator;

    public function __construct(VatNumberValidator $validator)
    {
        $this->validator = $validator;
    }

    public function validate(ValidateVatNumber $command): VatNumberValidation
    {
        return $this->validator->validate($command->getVatNumber());
    }
}
