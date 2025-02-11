<?php
declare(strict_types=1);

namespace Thinktomorrow\Trader\Application\VatRate;

use Thinktomorrow\Trader\Domain\Model\VatRate\VatRateId;

class DeleteVatRate
{
    private string $taxRateProfileId;

    public function __construct(string $taxRateProfileId)
    {
        $this->taxRateProfileId = $taxRateProfileId;
    }

    public function getTaxRateProfileId(): VatRateId
    {
        return VatRateId::fromString($this->taxRateProfileId);
    }
}
