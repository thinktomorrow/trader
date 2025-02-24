<?php
declare(strict_types=1);

namespace Thinktomorrow\Trader\Domain\Model\VatRate\Events;

use Thinktomorrow\Trader\Domain\Model\VatRate\BaseRateId;
use Thinktomorrow\Trader\Domain\Model\VatRate\VatRateId;

readonly class BaseRateDeleted
{
    public function __construct(public BaseRateId $baseRateId, public VatRateId $vatRateId)
    {
    }
}
