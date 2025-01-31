<?php
declare(strict_types=1);

namespace Thinktomorrow\Trader\Application\TaxRateProfile;

use Thinktomorrow\Trader\Domain\Common\Taxes\TaxRate;
use Thinktomorrow\Trader\Domain\Model\TaxRateProfile\TaxRateDoubleId;
use Thinktomorrow\Trader\Domain\Model\TaxRateProfile\TaxRateProfileId;

class UpdateTaxRateDouble
{
    private string $taxRateDoubleId;
    private string $taxRateProfileId;
    private string $originalRate;
    private string $rate;

    public function __construct(string $taxRateDoubleId, string $taxRateProfileId, string $originalRate, string $rate)
    {
        $this->taxRateDoubleId = $taxRateDoubleId;
        $this->taxRateProfileId = $taxRateProfileId;
        $this->originalRate = $originalRate;
        $this->rate = $rate;
    }

    public function getTaxRateDoubleId(): TaxRateDoubleId
    {
        return TaxRateDoubleId::fromString($this->taxRateDoubleId);
    }

    public function getTaxRateProfileId(): TaxRateProfileId
    {
        return TaxRateProfileId::fromString($this->taxRateProfileId);
    }

    public function getOriginalRate(): TaxRate
    {
        return TaxRate::fromString($this->originalRate);
    }

    public function getRate(): TaxRate
    {
        return TaxRate::fromString($this->rate);
    }
}
