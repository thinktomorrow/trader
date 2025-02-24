<?php
declare(strict_types=1);

namespace Tests\Acceptance\Taxon;

use Tests\Infrastructure\Vine\TaxonHelpers;
use Thinktomorrow\Trader\Application\Taxon\CreateTaxon;
use Thinktomorrow\Trader\Application\Taxon\DeleteTaxon;
use Thinktomorrow\Trader\Domain\Model\Taxon\Events\TaxonDeleted;
use Thinktomorrow\Trader\Domain\Model\Taxon\Exceptions\CouldNotFindTaxon;

class DeleteTaxonTest extends TaxonContext
{
    use TaxonHelpers;

    public function test_it_can_delete_taxon()
    {
        $taxonId = $this->taxonApplication->createTaxon(new CreateTaxon('taxon-key', 'nl', []));

        $this->taxonApplication->deleteTaxon(new DeleteTaxon($taxonId->get()));

        $this->expectException(CouldNotFindTaxon::class);
        $taxon = $this->taxonRepository->find($taxonId);

        $this->assertEquals([
            new TaxonDeleted($taxonId),
        ], $this->eventDispatcher->releaseDispatchedEvents());
    }

    public function test_deleting_taxon_moves_child_taxa_to_level_above()
    {
        $taxonRootId = $this->taxonApplication->createTaxon(new CreateTaxon('taxon-key-root', 'nl', []));
        $this->taxonRepository->setNextReference('abc');
        $taxonId = $this->taxonApplication->createTaxon(new CreateTaxon('taxon-key', 'nl', [], $taxonRootId->get()));

        $this->taxonRepository->setNextReference('def');
        $nestedTaxonId = $this->taxonApplication->createTaxon(new CreateTaxon('taxon-key-nested', 'fr', [], $taxonId->get()));

        $this->taxonApplication->deleteTaxon(new DeleteTaxon($taxonId->get()));

        $taxon = $this->taxonRepository->find($nestedTaxonId);
        $this->assertEquals($taxonRootId, $taxon->getParentId());
    }
}
