<?php
declare(strict_types=1);

namespace Tests\Acceptance\Taxon;

use Tests\Infrastructure\Vine\TaxonHelpers;
use Thinktomorrow\Trader\Application\Taxon\CreateTaxon;
use Thinktomorrow\Trader\Application\Taxon\MoveTaxon;
use Thinktomorrow\Trader\Domain\Model\Taxon\Exceptions\CouldNotMoveTaxon;

class MoveTaxonTest extends TaxonContext
{
    use TaxonHelpers;

    public function test_it_can_move_a_taxon_to_root()
    {
        $taxonRootId = $this->taxonApplication->createTaxon(new CreateTaxon('bbb', 'taxon-key-root', 'nl', []));
        $this->taxonRepository->setNextReference('abc');
        $taxonId = $this->taxonApplication->createTaxon(new CreateTaxon('bbb', 'taxon-key', 'nl', [], $taxonRootId->get()));

        $taxon = $this->taxonRepository->find($taxonId);
        $this->assertEquals($taxonRootId->get(), $taxon->getMappedData()['parent_id']);

        $this->taxonApplication->moveTaxon(new MoveTaxon($taxonId->get()));

        $taxon = $this->taxonRepository->find($taxonId);
        $this->assertNull($taxon->getMappedData()['parent_id']);
    }

    public function test_it_can_move_a_taxon_to_another_taxon()
    {
        $taxonRootId = $this->taxonApplication->createTaxon(new CreateTaxon('bbb', 'taxon-key-root', 'nl', []));
        $this->taxonRepository->setNextReference('abc');
        $taxonId = $this->taxonApplication->createTaxon(new CreateTaxon('bbb', 'taxon-key', 'nl', []));

        $taxon = $this->taxonRepository->find($taxonId);
        $this->assertNull($taxon->getMappedData()['parent_id']);

        $this->taxonApplication->moveTaxon(new MoveTaxon($taxonId->get(), $taxonRootId->get()));

        $taxon = $this->taxonRepository->find($taxonId);
        $this->assertEquals($taxonRootId->get(), $taxon->getMappedData()['parent_id']);
    }

    public function test_it_cannot_move_a_taxon_to_another_taxon_belonging_to_different_taxonomy(): void
    {
        $this->expectException(CouldNotMoveTaxon::class);
        $this->expectExceptionMessage('Could not move taxon because target taxon "abc" does not belong to the same taxonomy "bbb"');

        $taxonRootId = $this->taxonApplication->createTaxon(new CreateTaxon('bbb', 'taxon-key-root', 'nl', []));
        $this->taxonRepository->setNextReference('abc');
        $targetTaxonId = $this->taxonApplication->createTaxon(new CreateTaxon('ccc', 'taxon-key', 'nl', []));
        $this->taxonRepository->setNextReference('def');
        $taxonId = $this->taxonApplication->createTaxon(new CreateTaxon('bbb', 'taxon-key', 'nl', [], $taxonRootId->get()));

        // Attempt to move a taxon under a different taxonomy
        $this->taxonApplication->moveTaxon(new MoveTaxon($taxonId->get(), 'abc'));

    }
}
