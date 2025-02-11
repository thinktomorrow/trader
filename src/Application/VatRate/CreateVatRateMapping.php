<?php
declare(strict_types=1);

namespace Thinktomorrow\Trader\Application\VatRate;

use Thinktomorrow\Trader\Domain\Model\VatRate\VatRateId;

class CreateVatRateMapping
{
    private string $baseRateId;
    private string $targetRateId;

    public function __construct(string $baseRateId, string $targetRateId)
    {
        $this->baseRateId = $baseRateId;
        $this->targetRateId = $targetRateId;
    }

    public function getBaseRateId(): VatRateId
    {
        return VatRateId::fromString($this->baseRateId);
    }

    public function getTargetRateId(): VatRateId
    {
        return VatRateId::fromString($this->targetRateId);
    }
}
