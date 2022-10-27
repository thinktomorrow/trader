<?php
declare(strict_types=1);

namespace Tests\Acceptance\Taxon;

use Tests\Infrastructure\Vine\TaxonHelpers;
use Thinktomorrow\Trader\Application\Taxon\CreateTaxon;
use Thinktomorrow\Trader\Application\Taxon\MoveTaxon;

class MoveTaxonTest extends TaxonContext
{
    use TaxonHelpers;

    /** @test */
    public function it_can_move_a_taxon_to_root()
    {
        $taxonRootId = $this->taxonApplication->createTaxon(new CreateTaxon('taxon-key-root', 'nl', []));
        $this->taxonRepository->setNextReference('abc');
        $taxonId = $this->taxonApplication->createTaxon(new CreateTaxon('taxon-key', 'nl', [], $taxonRootId->get()));

        $taxon = $this->taxonRepository->find($taxonId);
        $this->assertEquals($taxonRootId->get(), $taxon->getMappedData()['parent_id']);

        $this->taxonApplication->moveTaxon(new MoveTaxon($taxonId->get()));

        $taxon = $this->taxonRepository->find($taxonId);
        $this->assertNull($taxon->getMappedData()['parent_id']);
    }

    /** @test */
    public function it_can_move_a_taxon_to_another_taxon()
    {
        $taxonRootId = $this->taxonApplication->createTaxon(new CreateTaxon('taxon-key-root', 'nl', []));
        $this->taxonRepository->setNextReference('abc');
        $taxonId = $this->taxonApplication->createTaxon(new CreateTaxon('taxon-key', 'nl', []));

        $taxon = $this->taxonRepository->find($taxonId);
        $this->assertNull($taxon->getMappedData()['parent_id']);

        $this->taxonApplication->moveTaxon(new MoveTaxon($taxonId->get(), $taxonRootId->get()));

        $taxon = $this->taxonRepository->find($taxonId);
        $this->assertEquals($taxonRootId->get(), $taxon->getMappedData()['parent_id']);
    }
}
