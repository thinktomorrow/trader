<?php
declare(strict_types=1);

namespace Thinktomorrow\Trader\Application\VatRate;

use Thinktomorrow\Trader\Domain\Common\Taxes\TaxRate;
use Thinktomorrow\Trader\Domain\Model\Country\CountryId;
use Thinktomorrow\Trader\Domain\Model\VatRate\VatRateId;

class UpdateVatRate
{
    private string $vatRateId;
    private string $rate;
    private array $data;

    public function __construct(string $vatRateId, string $rate, array $data)
    {
        $this->vatRateId = $vatRateId;
        $this->rate = $rate;
        $this->data = $data;
    }

    public function getVatRateId(): VatRateId
    {
        return VatRateId::fromString($this->vatRateId);
    }

    public function getRate(): TaxRate
    {
        return TaxRate::fromString($this->rate);
    }

    public function getData(): array
    {
        return $this->data;
    }
}
