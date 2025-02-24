<?php
declare(strict_types=1);

namespace Tests\Unit\Model;

use PHPUnit\Framework\TestCase;
use Thinktomorrow\Trader\Domain\Common\Vat\VatPercentage;
use Thinktomorrow\Trader\Domain\Model\Country\CountryId;
use Thinktomorrow\Trader\Domain\Model\VatRate\BaseRate;
use Thinktomorrow\Trader\Domain\Model\VatRate\BaseRateId;
use Thinktomorrow\Trader\Domain\Model\VatRate\VatRate;
use Thinktomorrow\Trader\Domain\Model\VatRate\VatRateId;
use Thinktomorrow\Trader\Domain\Model\VatRate\VatRateState;

class VatRateTest extends TestCase
{
    public function test_it_can_create_a_vat_rate()
    {
        $vatRate = VatRate::create(
            $vatRateId = VatRateId::fromString('yyy'),
            $countryId = CountryId::fromString('BE'),
            $rate = VatPercentage::fromString('21'),
            $isStandard = false
        );

        $this->assertEquals([
            'vat_rate_id' => 'yyy',
            'country_id' => 'BE',
            'rate' => '21',
            'state' => VatRateState::online->value,
            'is_standard' => false,
            'data' => json_encode([]),
        ], $vatRate->getMappedData());

        $this->assertEquals([
            BaseRate::class => [],
        ], $vatRate->getChildEntities());
    }

    public function test_it_can_be_build_from_raw_data()
    {
        $vatRate = $this->createdVatRate();

        $this->assertEquals(VatRateId::fromString('yyy'), $vatRate->vatRateId);
        $this->assertEquals(CountryId::fromString('BE'), $vatRate->countryId);
        $this->assertEquals(VatPercentage::fromString('21'), $vatRate->getRate());
        $this->assertEquals(VatRateState::offline, $vatRate->getState());
        $this->assertEquals('bar', $vatRate->getData('foo'));
        $this->assertCount(2, $vatRate->getChildEntities()[BaseRate::class]);
        $this->assertEquals([
            'base_rate_id' => 'xxx',
            'origin_vat_rate_id' => 'aaa',
            'target_vat_rate_id' => 'yyy',
        ], $vatRate->getChildEntities()[BaseRate::class][0]);
    }

    public function test_it_can_update_rate()
    {
        $vatRate = $this->createdVatRate();
        $vatRate->updateRate(VatPercentage::fromString('25'));

        $this->assertEquals(VatPercentage::fromString('25'), $vatRate->getRate());
    }

    public function test_it_can_add_a_base_rate()
    {
        $vatRate = $this->createdVatRate();

        $vatRate->addBaseRate(
            BaseRate::create(
                BaseRateId::fromString('zzz'),
                VatRateId::fromString('bbb'),
                VatRateId::fromString('xxx'),
                VatPercentage::fromString('10'),
            )
        );

        $this->assertCount(3, $vatRate->getChildEntities()[BaseRate::class]);
    }

    public function test_it_can_delete_base_rate()
    {
        $vatRate = $this->createdVatRate();

        $vatRate->deleteBaseRate(BaseRateId::fromString('xxx'));

        $this->assertCount(1, $vatRate->getChildEntities()[BaseRate::class]);
    }

    public function test_it_can_check_if_base_rate_applies_for_a_rate()
    {
        $vatRate = $this->createdVatRate();

        $this->assertFalse($vatRate->hasBaseRateOf(VatPercentage::fromString('15')));
        $this->assertTrue($vatRate->hasBaseRateOf(VatPercentage::fromString('12')));
        $this->assertTrue($vatRate->hasBaseRateOf(VatPercentage::fromString('10')));
    }

    private function createdVatRate(): VatRate
    {
        return VatRate::fromMappedData([
            'vat_rate_id' => 'yyy',
            'country_id' => 'BE',
            'rate' => '21',
            'state' => VatRateState::offline->value,
            'is_standard' => false,
            'data' => json_encode(['foo' => 'bar']),
        ], [
            BaseRate::class => [
                [
                    'base_rate_id' => 'xxx',
                    'origin_vat_rate_id' => 'aaa',
                    'target_vat_rate_id' => 'yyy',
                    'rate' => '10',
                ],
                [
                    'base_rate_id' => 'zzz',
                    'origin_vat_rate_id' => 'bbb',
                    'target_vat_rate_id' => 'yyy',
                    'rate' => '12',
                ],
            ],
        ]);
    }
}
