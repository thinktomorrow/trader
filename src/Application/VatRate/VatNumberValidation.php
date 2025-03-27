<?php
declare(strict_types=1);

namespace Thinktomorrow\Trader\Application\VatRate;

use Thinktomorrow\Trader\Domain\Model\VatRate\VatNumber;
use Thinktomorrow\Trader\Domain\Model\VatRate\VatNumberValidationState;

class VatNumberValidation
{
    public function __construct(
        public readonly VatNumber                $vatNumber,
        public readonly VatNumberValidationState $state,
        public readonly array                    $data)
    {
    }
}
