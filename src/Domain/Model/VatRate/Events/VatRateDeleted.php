<?php
declare(strict_types=1);

namespace Thinktomorrow\Trader\Domain\Model\VatRate\Events;

use Thinktomorrow\Trader\Domain\Model\VatRate\VatRateId;

class VatRateDeleted
{
    public function __construct(public readonly VatRateId $vatRateId)
    {
    }
}
