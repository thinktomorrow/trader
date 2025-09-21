<?php
declare(strict_types=1);

namespace Tests\Infrastructure\Vine;

use Tests\Infrastructure\Common\Catalog;
use Tests\Infrastructure\TestCase;

final class TaxaSelectOptionsTest extends TestCase
{
    public function test_it_can_retrieve_options()
    {
        foreach (Catalog::drivers() as $catalog) {

            // Catalog setup
            $catalog->createTaxonomy();
            $taxon = $catalog->createTaxon();

            // Compose options
            $options = $catalog->repos->taxaSelectOptions()->getByTaxonomy('taxonomy-aaa');

            $this->assertCount(1, $options);
            $this->assertEquals([
                'taxon-aaa' => 'taxon-aaa title nl',
            ], $options);
        }
    }

    public function test_it_can_retrieve_tree_as_options()
    {
        foreach (Catalog::drivers() as $catalog) {

            // Catalog setup
            $catalog->createTaxonomy();
            $taxon = $catalog->createTaxon();
            $taxon2 = $catalog->createTaxon('taxon-bbb', 'taxonomy-aaa', $taxon->taxonId->get());
            $taxon3 = $catalog->createTaxon('taxon-ccc', 'taxonomy-aaa', $taxon2->taxonId->get());

            // Compose options
            $options = $catalog->repos->taxaSelectOptions()->getByTaxonomy('taxonomy-aaa');

            $this->assertCount(3, $options);
            $this->assertEquals([
                'taxon-aaa' => 'taxon-aaa title nl',
                'taxon-bbb' => 'taxon-aaa title nl: taxon-bbb title nl',
                'taxon-ccc' => 'taxon-aaa title nl: taxon-bbb title nl > taxon-ccc title nl',
            ], $options);
        }
    }

    public function test_it_can_retrieve_options_for_multiselect()
    {
        foreach (Catalog::drivers() as $catalog) {

            // Catalog setup
            $catalog->createTaxonomy();
            $taxon = $catalog->createTaxon();
            $taxon2 = $catalog->createTaxon('taxon-bbb', 'taxonomy-aaa', $taxon->taxonId->get());
            $taxon3 = $catalog->createTaxon('taxon-ccc', 'taxonomy-aaa', $taxon2->taxonId->get());

            // Compose options
            $options = $catalog->repos->taxaSelectOptions()->getForMultiselectByTaxonomy('taxonomy-aaa');

            $this->assertCount(3, $options);
            $this->assertEquals([
                ['label' => 'taxon-aaa title nl', 'value' => 'taxon-aaa'],
                ['label' => 'taxon-aaa title nl: taxon-bbb title nl', 'value' => 'taxon-bbb'],
                ['label' => 'taxon-aaa title nl: taxon-bbb title nl > taxon-ccc title nl', 'value' => 'taxon-ccc'],
            ], $options);
        }
    }
}
