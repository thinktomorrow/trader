<?php
declare(strict_types=1);

namespace Tests\Acceptance\VatRate;

use Thinktomorrow\Trader\Application\VatRate\CreateBaseRate;
use Thinktomorrow\Trader\Application\VatRate\CreateVatRate;
use Thinktomorrow\Trader\Domain\Common\Vat\VatPercentage;
use Thinktomorrow\Trader\Domain\Model\Country\CountryId;
use Thinktomorrow\Trader\Domain\Model\VatRate\BaseRate;
use Thinktomorrow\Trader\Domain\Model\VatRate\BaseRateId;
use Thinktomorrow\Trader\Domain\Model\VatRate\VatRateId;

class CreateVatRateTest extends VatRateContext
{
    public function test_it_can_create_a_vat_rate()
    {
        $vatRateId = $this->catalogContext->apps()->vatRateApplication()->createVatRate(new CreateVatRate(
            'BE',
            '21',
            ['foo' => 'bar']
        ));

        $vatRate = $this->catalogContext->repos()->vatRateRepository()->find($vatRateId);

        $this->assertInstanceOf(VatRateId::class, $vatRateId);
        $this->assertEquals($vatRateId, $vatRate->vatRateId);
        $this->assertEquals(CountryId::fromString('BE'), $vatRate->countryId);
        $this->assertEquals(['foo' => 'bar'], $vatRate->getData());
    }

    public function test_it_can_create_a_base_rate()
    {
        [
            'originVatRateId' => $originVatRateId,
            'targetVatRateId' => $targetVatRateId,
            'baseRateId' => $baseRateId
        ] = $this->createBaseRateStub();

        $baseRate = $this->catalogContext->repos()->vatRateRepository()->find($targetVatRateId)->findBaseRate($baseRateId);

        $this->assertInstanceOf(BaseRateId::class, $baseRateId);

        $this->assertInstanceOf(BaseRate::class, $baseRate);
        $this->assertEquals($baseRateId, $baseRate->baseRateId);
        $this->assertEquals($originVatRateId, $baseRate->originVatRateId);
        $this->assertEquals($targetVatRateId, $baseRate->targetVatRateId);
        $this->assertEquals(VatPercentage::fromString('21'), $baseRate->rate);
    }

    public function test_base_rate_belongs_to_target_rate()
    {
        $originVatRateId = $this->catalogContext->apps()->vatRateApplication()->createVatRate(new CreateVatRate(
            'BE',
            '21',
            ['foo' => 'bar']
        ));

        $this->catalogContext->repos()->vatRateRepository()->setNextReference('zzz-123');
        $targetVatRateId = $this->catalogContext->apps()->vatRateApplication()->createVatRate(new CreateVatRate(
            'NL',
            '20',
            ['foo' => 'baz']
        ));

        $this->catalogContext->apps()->vatRateApplication()->createBaseRate(new CreateBaseRate($originVatRateId->get(), $targetVatRateId->get()));

        $this->assertCount(0, $this->catalogContext->repos()->vatRateRepository()->find($originVatRateId)->getBaseRates());
        $this->assertCount(1, $this->catalogContext->repos()->vatRateRepository()->find($targetVatRateId)->getBaseRates());
    }
}
