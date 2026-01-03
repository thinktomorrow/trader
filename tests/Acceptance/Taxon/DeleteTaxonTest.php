<?php
declare(strict_types=1);

namespace Tests\Acceptance\Taxon;

use Tests\Acceptance\TestCase;
use Tests\Infrastructure\Vine\TaxonHelpers;
use Thinktomorrow\Trader\Application\Taxon\CreateTaxon;
use Thinktomorrow\Trader\Application\Taxon\DeleteTaxon;
use Thinktomorrow\Trader\Domain\Model\Taxon\Events\TaxonDeleted;
use Thinktomorrow\Trader\Domain\Model\Taxon\Exceptions\CouldNotFindTaxon;

class DeleteTaxonTest extends TestCase
{
    use TaxonHelpers;

    public function test_it_can_delete_taxon()
    {
        $taxonId = $this->catalogContext->apps()->taxonApplication()->createTaxon(new CreateTaxon('bbb', 'taxon-key', 'nl', []));

        $this->catalogContext->apps()->taxonApplication()->deleteTaxon(new DeleteTaxon($taxonId->get()));

        $this->expectException(CouldNotFindTaxon::class);
        $taxon = $this->catalogContext->repos()->taxonRepository()->find($taxonId);

        $this->assertEquals([
            new TaxonDeleted($taxonId),
        ], $this->eventDispatcher->releaseDispatchedEvents());
    }

    public function test_deleting_taxon_moves_child_taxa_to_level_above()
    {
        $taxonRootId = $this->catalogContext->apps()->taxonApplication()->createTaxon(new CreateTaxon('bbb', 'taxon-key-root', 'nl', []));
        $this->catalogContext->repos()->taxonRepository()->setNextReference('abc');
        $taxonId = $this->catalogContext->apps()->taxonApplication()->createTaxon(new CreateTaxon('bbb', 'taxon-key', 'nl', [], $taxonRootId->get()));

        $this->catalogContext->repos()->taxonRepository()->setNextReference('def');
        $nestedTaxonId = $this->catalogContext->apps()->taxonApplication()->createTaxon(new CreateTaxon('bbb', 'taxon-key-nested', 'fr', [], $taxonId->get()));

        $this->catalogContext->apps()->taxonApplication()->deleteTaxon(new DeleteTaxon($taxonId->get()));

        $taxon = $this->catalogContext->repos()->taxonRepository()->find($nestedTaxonId);
        $this->assertEquals($taxonRootId, $taxon->getParentId());
    }
}
