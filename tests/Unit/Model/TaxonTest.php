<?php
declare(strict_types=1);

namespace Tests\Unit\Model;

use PHPUnit\Framework\TestCase;
use Thinktomorrow\Trader\Domain\Model\Taxon\Taxon;
use Thinktomorrow\Trader\Domain\Model\Taxon\TaxonId;
use Thinktomorrow\Trader\Domain\Model\Taxon\TaxonKey;
use Thinktomorrow\Trader\Domain\Model\Taxon\TaxonState;
use Thinktomorrow\Trader\Domain\Model\Taxon\Exceptions\InvalidParentTaxonId;

class TaxonTest extends TestCase
{
    /** @test */
    public function it_can_create_a_taxon()
    {
        $taxon = Taxon::create(
            TaxonId::fromString('aaa'),
            TaxonKey::fromString('taxon-key'),
            TaxonId::fromString('parent-aaa'),
        );

        $taxon->addData(['foo' => 'bar']);

        $this->assertEquals([
            'taxon_id' => 'aaa',
            'key' => 'taxon-key',
            'state' => TaxonState::online->value,
            'order' => 0,
            'parent_id' => 'parent-aaa',
            'data' => json_encode(['foo' => 'bar']),
        ], $taxon->getMappedData());
    }

    /** @test */
    public function parent_id_is_not_required()
    {
        $taxon = Taxon::create(
            TaxonId::fromString('aaa'),
            TaxonKey::fromString('taxon-key'),
        );

        $this->assertNull($taxon->getMappedData()['parent_id']);
    }

    /** @test */
    public function it_can_change_parent()
    {
        $taxon = Taxon::create(
            TaxonId::fromString('aaa'),
            TaxonKey::fromString('taxon-key'),
        );

        $taxon->changeParent(TaxonId::fromString('bbb'), );

        $this->assertEquals('bbb', $taxon->getMappedData()['parent_id']);
    }

    /** @test */
    public function it_can_move_taxon_to_root()
    {
        $taxon = Taxon::create(
            TaxonId::fromString('aaa'),
            TaxonKey::fromString('taxon-key'),
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
            TaxonKey::fromString('taxon-key'),
            TaxonId::fromString('aaa'),
        );
    }

    /** @test */
    public function it_can_change_state()
    {
        $taxon = Taxon::create(
            TaxonId::fromString('aaa'),
            TaxonKey::fromString('taxon-key'),
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
            'key' => 'taxon-key',
            'state' => TaxonState::offline->value,
            'order' => 5,
            'parent_id' => 'parent-yyy',
            'data' => json_encode(['foo' => 'bar']),
        ], $taxon->getMappedData());
    }

    private function createdTaxon(): Taxon
    {
        return Taxon::fromMappedData([
            'taxon_id' => 'yyy',
            'key' => 'taxon-key',
            'state' => TaxonState::offline->value,
            'order' => 5,
            'parent_id' => 'parent-yyy',
            'data' => json_encode(['foo' => 'bar']),
        ], []);
    }
}
