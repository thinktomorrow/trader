<?php
declare(strict_types=1);

namespace Tests\Acceptance\VatRate;

use Thinktomorrow\Trader\Application\VatRate\CreateVatRate;
use Thinktomorrow\Trader\Application\VatRate\UpdateVatRate;
use Thinktomorrow\Trader\Domain\Common\Taxes\TaxRate;
use Thinktomorrow\Trader\Domain\Model\Country\CountryId;

class UpdateVatRateTest extends VatRateContext
{
    public function test_it_can_update_a_vat_rate()
    {
        $vatRateId = $this->vatRateApplication->createVatRate(new CreateVatRate(
            'BE', '21', ['foo' => 'bar']
        ));

        $this->vatRateApplication->updateVatRate(new UpdateVatRate(
            $vatRateId->get(),
            '20',
            ['foo' => 'baz']
        ));

        $vatRate = $this->vatRateRepository->find($vatRateId);

        $this->assertEquals(TaxRate::fromString('20'), $vatRate->getRate());
        $this->assertEquals(['foo' => 'baz'], $vatRate->getData());
        $this->assertEquals(CountryId::fromString('BE'), $vatRate->countryId);
    }
}
