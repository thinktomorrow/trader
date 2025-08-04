<?php
declare(strict_types=1);

namespace Tests\Acceptance\Taxonomy;

use Thinktomorrow\Trader\Application\Taxonomy\CreateTaxonomy;
use Thinktomorrow\Trader\Domain\Model\Taxonomy\TaxonomyState;
use Thinktomorrow\Trader\Domain\Model\Taxonomy\TaxonomyType;

class CreateTaxonomyTest extends TaxonomyContext
{
    public function test_it_can_create_a_taxonomy()
    {
        $taxonomyId = $this->taxonomyApplication->createTaxonomy(new CreateTaxonomy('property', true, false, false, ['foo' => 'bar']));

        $taxonomy = $this->taxonomyRepository->find($taxonomyId);

        $this->assertEquals(TaxonomyType::from('property'), $taxonomy->getType());
        $this->assertEquals(TaxonomyState::online, $taxonomy->getState());
        $this->assertEquals(true, $taxonomy->showsAsGridFilter());
        $this->assertEquals(false, $taxonomy->showsInGrid());
        $this->assertEquals(false, $taxonomy->allowsMultipleValues());
        $this->assertEquals(['foo' => 'bar'], $taxonomy->getData());
    }
}
