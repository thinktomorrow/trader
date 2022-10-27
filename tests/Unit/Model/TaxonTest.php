<?php
declare(strict_types=1);

namespace Tests\Unit\Model;

use PHPUnit\Framework\TestCase;
use Thinktomorrow\Trader\Domain\Common\Locale;
use Thinktomorrow\Trader\Domain\Model\Taxon\Events\TaxonKeyUpdated;
use Thinktomorrow\Trader\Domain\Model\Taxon\Exceptions\InvalidParentTaxonId;
use Thinktomorrow\Trader\Domain\Model\Taxon\Exceptions\InvalidTaxonIdOnTaxonKey;
use Thinktomorrow\Trader\Domain\Model\Taxon\Taxon;
use Thinktomorrow\Trader\Domain\Model\Taxon\TaxonId;
use Thinktomorrow\Trader\Domain\Model\Taxon\TaxonKey;
use Thinktomorrow\Trader\Domain\Model\Taxon\TaxonKeyId;
use Thinktomorrow\Trader\Domain\Model\Taxon\TaxonState;

class TaxonTest extends TestCase
{
    /** @test */
    public function it_can_create_a_taxon()
    {
        $taxon = Taxon::create(
            TaxonId::fromString('aaa'),
            TaxonId::fromString('parent-aaa'),
        );

        $taxon->updateTaxonKeys([
            $taxonKey = TaxonKey::create($taxon->taxonId, TaxonKeyId::fromString('xxx'), Locale::fromString('nl_BE')),
        ]);
        $taxon->addData(['foo' => 'bar']);

        $this->assertEquals([
            'taxon_id' => 'aaa',
            'state' => TaxonState::online->value,
            'order' => 0,
            'parent_id' => 'parent-aaa',
            'data' => json_encode(['foo' => 'bar']),
        ], $taxon->getMappedData());

        $this->assertEquals([$taxonKey->getMappedData()], $taxon->getChildEntities()[TaxonKey::class]);
    }

    /** @test */
    public function parent_id_is_not_required()
    {
        $taxon = Taxon::create(
            TaxonId::fromString('aaa'),
        );

        $this->assertNull($taxon->getMappedData()['parent_id']);
    }

    /** @test */
    public function it_can_change_parent()
    {
        $taxon = Taxon::create(
            TaxonId::fromString('aaa'),
        );

        $taxon->changeParent(TaxonId::fromString('bbb'), );

        $this->assertEquals('bbb', $taxon->getMappedData()['parent_id']);
    }

    /** @test */
    public function it_can_move_taxon_to_root()
    {
        $taxon = Taxon::create(
            TaxonId::fromString('aaa'),
            TaxonId::fromString('bbb')
        );

        $taxon->moveToRoot();

        $this->assertNull($taxon->getMappedData()['parent_id']);
    }

    /** @test */
    public function parent_id_cannot_be_same_as_own_id()
    {
        $this->expectException(InvalidParentTaxonId::class);

        Taxon::create(
            TaxonId::fromString('aaa'),
            TaxonId::fromString('aaa'),
        );
    }

    /** @test */
    public function it_can_change_state()
    {
        $taxon = Taxon::create(
            TaxonId::fromString('aaa'),
            TaxonId::fromString('bbb')
        );

        $taxon->changeState(TaxonState::offline);

        $this->assertEquals(TaxonState::offline->value, $taxon->getMappedData()['state']);
    }

    /** @test */
    public function it_can_be_build_from_raw_data()
    {
        $taxon = $this->createdTaxon();

        $this->assertEquals(TaxonId::fromString('yyy'), $taxon->taxonId);

        $this->assertEquals([
            'taxon_id' => 'yyy',
            'state' => TaxonState::offline->value,
            'order' => 5,
            'parent_id' => 'parent-yyy',
            'data' => json_encode(['foo' => 'bar']),
        ], $taxon->getMappedData());
    }

    public function test_it_can_add_taxon_key()
    {
        $taxon = $this->createdTaxon();

        $taxon->updateTaxonKeys([
            $taxonKey = TaxonKey::create($taxon->taxonId, TaxonKeyId::fromString('xxx'), Locale::fromString('nl_BE')),
        ]);

        $this->assertEquals([$taxonKey], $taxon->getTaxonKeys());
    }

    public function test_it_can_update_taxon_key()
    {
        $taxon = $this->createdTaxon();

        $taxon->updateTaxonKeys([
            $taxonKey = TaxonKey::create($taxon->taxonId, TaxonKeyId::fromString('xxx'), Locale::fromString('nl_BE')),
        ]);

        $taxon->updateTaxonKeys([
            $taxonKeyUpdated = TaxonKey::create($taxon->taxonId, TaxonKeyId::fromString('yyy'), Locale::fromString('nl_BE')),
        ]);

        $this->assertEquals([$taxonKeyUpdated], $taxon->getTaxonKeys());

        $this->assertEquals([new TaxonKeyUpdated($taxon->taxonId, Locale::fromString('nl_BE'), TaxonKeyId::fromString('xxx'), TaxonKeyId::fromString('yyy'))], $taxon->releaseEvents());
    }

    public function test_it_protects_against_invalid_taxon_id_on_taxon_key()
    {
        $this->expectException(InvalidTaxonIdOnTaxonKey::class);

        $taxon = $this->createdTaxon();

        $taxon->updateTaxonKeys([
            TaxonKey::create(TaxonId::fromString('invalid'), TaxonKeyId::fromString('xxx'), Locale::fromString('nl_BE')),
        ]);

        $this->assertEquals([], $taxon->getTaxonKeys());
    }

    public function test_taxon_key_is_per_locale()
    {
        $taxon = $this->createdTaxon();

        $taxon->updateTaxonKeys([
            $taxonKey = TaxonKey::create($taxon->taxonId, TaxonKeyId::fromString('xxx'), Locale::fromString('nl_BE')),
            $taxonKey2 = TaxonKey::create($taxon->taxonId, TaxonKeyId::fromString('xxx-fr'), Locale::fromString('fr_BE')),
        ]);

        // Override by locale
        $taxon->updateTaxonKeys([
            $taxonKey3 = TaxonKey::create($taxon->taxonId, TaxonKeyId::fromString('yyy'), Locale::fromString('nl_BE')),
        ]);

        $this->assertEquals([$taxonKey3, $taxonKey2], $taxon->getTaxonKeys());
    }

    public function test_it_can_check_if_taxon_has_taxon_key_id()
    {
        $taxon = $this->createdTaxon();

        $taxon->updateTaxonKeys([
            $taxonKey = TaxonKey::create($taxon->taxonId, TaxonKeyId::fromString('xxx'), Locale::fromString('nl_BE')),
            $taxonKey2 = TaxonKey::create($taxon->taxonId, TaxonKeyId::fromString('xxx-fr'), Locale::fromString('fr_BE')),
        ]);

        $this->assertTrue($taxon->hasTaxonKeyId($taxonKey->taxonKeyId));
        $this->assertTrue($taxon->hasTaxonKeyId($taxonKey2->taxonKeyId));
        $this->assertFalse($taxon->hasTaxonKeyId(TaxonKeyId::fromString('invalid')));
    }

    private function createdTaxon(): Taxon
    {
        return Taxon::fromMappedData([
            'taxon_id' => 'yyy',
            'state' => TaxonState::offline->value,
            'order' => 5,
            'parent_id' => 'parent-yyy',
            'data' => json_encode(['foo' => 'bar']),
        ], [
            TaxonKey::class => [

            ],
        ]);
    }
}
