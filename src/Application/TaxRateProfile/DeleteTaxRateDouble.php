<?php
declare(strict_types=1);

namespace Thinktomorrow\Trader\Application\TaxRateProfile;

use Thinktomorrow\Trader\Domain\Model\TaxRateProfile\TaxRateProfileId;
use Thinktomorrow\Trader\Domain\Model\TaxRateProfile\TaxRateDoubleId;

class DeleteTaxRateDouble
{
    private string $taxRateProfileId;
    private string $taxRateDoubleId;

    public function __construct(string $taxRateProfileId, string $taxRateDoubleId)
    {
        $this->taxRateProfileId = $taxRateProfileId;
        $this->taxRateDoubleId = $taxRateDoubleId;
    }

    public function getTaxRateProfileId(): TaxRateProfileId
    {
        return TaxRateProfileId::fromString($this->taxRateProfileId);
    }

    public function getTaxRateDoubleId(): TaxRateDoubleId
    {
        return TaxRateDoubleId::fromString($this->taxRateDoubleId);
    }
}
