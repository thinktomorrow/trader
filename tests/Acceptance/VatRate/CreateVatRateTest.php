<?php
declare(strict_types=1);

namespace Tests\Acceptance\VatRate;

use Thinktomorrow\Trader\Application\VatRate\CreateVatRate;
use Thinktomorrow\Trader\Application\VatRate\CreateBaseRate;
use Thinktomorrow\Trader\Domain\Common\Taxes\TaxRate;
use Thinktomorrow\Trader\Domain\Model\Country\CountryId;
use Thinktomorrow\Trader\Domain\Model\VatRate\VatRateId;
use Thinktomorrow\Trader\Domain\Model\VatRate\BaseRate;
use Thinktomorrow\Trader\Domain\Model\VatRate\BaseRateId;

class CreateVatRateTest extends VatRateContext
{
    public function test_it_can_create_a_vat_rate()
    {
        $vatRateId = $this->vatRateApplication->createVatRate(new CreateVatRate(
            'BE', '21', ['foo' => 'bar']
        ));

        $vatRate = $this->vatRateRepository->find($vatRateId);

        $this->assertInstanceOf(VatRateId::class, $vatRateId);
        $this->assertEquals($vatRateId, $vatRate->vatRateId);
        $this->assertEquals(CountryId::fromString('BE'), $vatRate->countryId);
        $this->assertEquals(['foo' => 'bar'], $vatRate->getData());
    }

    public function test_it_can_create_a_base_rate()
    {
        $originVatRateId = $this->vatRateApplication->createVatRate(new CreateVatRate(
            'BE', '21', ['foo' => 'bar']
        ));

        $this->vatRateRepository->setNextReference('zzz-123');
        $targetVatRateId = $this->vatRateApplication->createVatRate(new CreateVatRate(
            'NL', '20', ['foo' => 'baz']
        ));

        $baseRateId = $this->vatRateApplication->createBaseRate(new CreateBaseRate($originVatRateId->get(), $targetVatRateId->get()));

        $baseRate = $this->vatRateRepository->find($targetVatRateId)->findBaseRate($baseRateId);

        $this->assertInstanceOf(BaseRateId::class, $baseRateId);

        $this->assertInstanceOf(BaseRate::class, $baseRate);
        $this->assertEquals($baseRateId, $baseRate->baseRateId);
        $this->assertEquals($originVatRateId, $baseRate->originVatRateId);
        $this->assertEquals($targetVatRateId, $baseRate->targetVatRateId);
        $this->assertEquals(TaxRate::fromString('21'), $baseRate->rate);
    }

    public function test_base_rate_belongs_to_target_rate()
    {
        $originVatRateId = $this->vatRateApplication->createVatRate(new CreateVatRate(
            'BE', '21', ['foo' => 'bar']
        ));

        $this->vatRateRepository->setNextReference('zzz-123');
        $targetVatRateId = $this->vatRateApplication->createVatRate(new CreateVatRate(
            'NL', '20', ['foo' => 'baz']
        ));

        $this->vatRateApplication->createBaseRate(new CreateBaseRate($originVatRateId->get(), $targetVatRateId->get()));

        $this->assertCount(0, $this->vatRateRepository->find($originVatRateId)->getBaseRates());
        $this->assertCount(1, $this->vatRateRepository->find($targetVatRateId)->getBaseRates());
    }
}
