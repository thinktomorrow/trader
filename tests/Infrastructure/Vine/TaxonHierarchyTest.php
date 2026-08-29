<?php

declare(strict_types=1);

namespace Tests\Infrastructure\Vine;

use Tests\Infrastructure\TestCase;
use Thinktomorrow\Trader\Application\Taxon\Tree\TaxonNode;
use Thinktomorrow\Trader\Infrastructure\Vine\VineTaxonHierarchy;
use Thinktomorrow\Trader\Testing\Catalog\CatalogContext;

final class TaxonHierarchyTest extends TestCase
{
    public function test_it_resolves_descendants_and_ancestors(): void
    {
        foreach (CatalogContext::drivers() as $catalog) {
            $taxonomy = $catalog->createTaxonomy();
            $catalog->createTaxon('root', $taxonomy->taxonomyId->get());
            $catalog->createTaxon('child', $taxonomy->taxonomyId->get(), 'root');
            $catalog->createTaxon('grandchild', $taxonomy->taxonomyId->get(), 'child');
            $catalog->createTaxon('sibling', $taxonomy->taxonomyId->get(), 'root');

            $tree = $catalog->repos()->taxonTreeRepository()->getTree();
            $root = $tree->find(fn (TaxonNode $taxon): bool => $taxon->getId() === 'root');
            $grandchild = $tree->find(fn (TaxonNode $taxon): bool => $taxon->getId() === 'grandchild');
            $sibling = $tree->find(fn (TaxonNode $taxon): bool => $taxon->getId() === 'sibling');
            $hierarchy = new VineTaxonHierarchy($catalog->repos()->taxonTreeRepository());

            $this->assertSame(
                ['root', 'child', 'grandchild', 'sibling'],
                array_map(fn (TaxonNode $taxon): string => $taxon->getId(), $hierarchy->descendants($root, true))
            );
            $this->assertSame(
                ['root', 'child'],
                array_map(fn (TaxonNode $taxon): string => $taxon->getId(), $hierarchy->ancestors($grandchild))
            );
            $this->assertTrue($hierarchy->isAncestorOf($root, $grandchild));
            $this->assertFalse($hierarchy->isAncestorOf($root, $root));
            $this->assertFalse($hierarchy->isAncestorOf($sibling, $grandchild));
        }
    }

    public function test_it_expands_configured_taxa_and_matches_assigned_descendants(): void
    {
        foreach (CatalogContext::drivers() as $catalog) {
            $taxonomy = $catalog->createTaxonomy();
            $catalog->createTaxon('root', $taxonomy->taxonomyId->get());
            $catalog->createTaxon('child', $taxonomy->taxonomyId->get(), 'root');
            $catalog->createTaxon('grandchild', $taxonomy->taxonomyId->get(), 'child');
            $catalog->createTaxon('unrelated', $taxonomy->taxonomyId->get());

            $hierarchy = new VineTaxonHierarchy($catalog->repos()->taxonTreeRepository());

            $this->assertSame(['root', 'child', 'grandchild'], $hierarchy->expandWithDescendants(['root']));
            $this->assertTrue($hierarchy->containsAny(['root'], ['grandchild']));
            $this->assertFalse($hierarchy->containsAny(['root'], ['unrelated']));
            $this->assertSame([], $hierarchy->expandWithDescendants(['missing']));
        }
    }
}
