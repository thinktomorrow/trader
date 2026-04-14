<?php

declare(strict_types=1);

namespace Tests\Acceptance\Taxonomy;

use Thinktomorrow\Trader\Application\Taxonomy\CreateTaxonomy;
use Thinktomorrow\Trader\Application\Taxonomy\DeleteTaxonomy;
use Thinktomorrow\Trader\Domain\Model\Taxonomy\Events\TaxonomyDeleted;
use Thinktomorrow\Trader\Domain\Model\Taxonomy\Exceptions\CouldNotFindTaxonomy;

class DeleteTaxonomyTest extends TaxonomyContext
{
    public function test_it_can_delete_taxonomy()
    {
        $taxonomyId = $this->taxonomyApplication->createTaxonomy(new CreateTaxonomy('property', true, false, false, ['foo' => 'bar']));

        $this->taxonomyApplication->deleteTaxonomy(new DeleteTaxonomy($taxonomyId->get()));

        $this->expectException(CouldNotFindTaxonomy::class);
        $this->taxonomyRepository->find($taxonomyId);

        $this->assertEquals([
            new TaxonomyDeleted($taxonomyId),
        ], $this->orderContext->apps()->getEventDispatcher()->releaseDispatchedEvents());
    }
}
