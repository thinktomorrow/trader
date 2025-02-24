<?php
declare(strict_types=1);

namespace Thinktomorrow\Trader\Application\VatRate;

use Thinktomorrow\Trader\Domain\Model\VatRate\VatRateId;

class DeleteVatRate
{
    private string $vatRateId;

    public function __construct(string $vatRateId)
    {
        $this->vatRateId = $vatRateId;
    }

    public function getVatRateId(): VatRateId
    {
        return VatRateId::fromString($this->vatRateId);
    }
}
