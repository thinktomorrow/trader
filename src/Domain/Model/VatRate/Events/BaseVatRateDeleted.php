<?php
declare(strict_types=1);

namespace Thinktomorrow\Trader\Domain\Model\VatRate\Events;

use Thinktomorrow\Trader\Domain\Model\VatRate\VatRateId;

readonly class BaseVatRateDeleted
{
    public function __construct(public VatRateId $vatRateId, public VatRateId $baseVatRateId)
    {
    }
}
