<?php
declare(strict_types=1);

namespace Thinktomorrow\Trader\Domain\Model\TaxRateProfile\Events;

use Thinktomorrow\Trader\Domain\Model\TaxRateProfile\TaxRateProfileId;

class TaxRateProfileDeleted
{
    public function __construct(public readonly TaxRateProfileId $taxRateProfileId)
    {
    }
}
