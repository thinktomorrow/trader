<?php
declare(strict_types=1);

namespace Thinktomorrow\Trader\Application\VatRate;

use Thinktomorrow\Trader\Domain\Model\VatRate\VatRateId;

class CreateBaseRate
{
    private string $originRateId;
    private string $targetRateId;

    public function __construct(string $originRateId, string $targetRateId)
    {
        $this->originRateId = $originRateId;
        $this->targetRateId = $targetRateId;
    }

    public function getOriginVatRateId(): VatRateId
    {
        return VatRateId::fromString($this->originRateId);
    }

    public function getTargetVatRateId(): VatRateId
    {
        return VatRateId::fromString($this->targetRateId);
    }
}
