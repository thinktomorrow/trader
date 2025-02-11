<?php
declare(strict_types=1);

namespace Thinktomorrow\Trader\Application\VatRate;

use Thinktomorrow\Trader\Domain\Model\VatRate\VatRateMappingId;

class DeleteVatRateMapping
{
    private string $vatRateMappingId;

    public function __construct(string $vatRateMappingId)
    {
        $this->vatRateMappingId = $vatRateMappingId;
    }

    public function getVatRateMappingId(): VatRateMappingId
    {
        return VatRateMappingId::fromString($this->vatRateMappingId);
    }
}
