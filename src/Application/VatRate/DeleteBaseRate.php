<?php
declare(strict_types=1);

namespace Thinktomorrow\Trader\Application\VatRate;

use Thinktomorrow\Trader\Domain\Model\VatRate\BaseRateId;
use Thinktomorrow\Trader\Domain\Model\VatRate\VatRateId;

class DeleteBaseRate
{
    private string $vatRateId;
    private string $baseRateId;

    public function __construct(string $vatRateId, string $baseRateId)
    {
        $this->vatRateId = $vatRateId;
        $this->baseRateId = $baseRateId;
    }

    public function getVatRateId(): VatRateId
    {
        return VatRateId::fromString($this->vatRateId);
    }

    public function getBaseRateId(): BaseRateId
    {
        return BaseRateId::fromString($this->baseRateId);
    }
}
