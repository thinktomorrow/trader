<?php
declare(strict_types=1);

namespace Thinktomorrow\Trader\Domain\Model\TaxRateProfile\Events;

use Thinktomorrow\Trader\Domain\Model\TaxRateProfile\TaxRateDoubleId;
use Thinktomorrow\Trader\Domain\Model\TaxRateProfile\TaxRateProfileId;

class TaxRateDoubleDeleted
{
    public function __construct(public readonly TaxRateProfileId $taxRateProfileId, public readonly TaxRateDoubleId $taxRateDoubleId)
    {
    }
}
