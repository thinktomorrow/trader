<?php
declare(strict_types=1);

namespace Thinktomorrow\Trader\Application\TaxRateProfile;

use Thinktomorrow\Trader\Domain\Model\TaxRateProfile\TaxRateProfileId;

class DeleteTaxRateProfile
{
    private string $taxRateProfileId;

    public function __construct(string $taxRateProfileId)
    {
        $this->taxRateProfileId = $taxRateProfileId;
    }

    public function getTaxRateProfileId(): TaxRateProfileId
    {
        return TaxRateProfileId::fromString($this->taxRateProfileId);
    }
}
