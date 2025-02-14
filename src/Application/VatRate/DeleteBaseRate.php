<?php
declare(strict_types=1);

namespace Thinktomorrow\Trader\Application\VatRate;

use Thinktomorrow\Trader\Domain\Model\VatRate\BaseRateId;
use Thinktomorrow\Trader\Domain\Model\VatRate\VatRateId;

class DeleteBaseRate
{
    private string $baseRateId;
    private string $vatRateId;

    public function __construct(string $baseRateId, string $vatRateId)
    {
        $this->baseRateId = $baseRateId;
        $this->vatRateId = $vatRateId;
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
