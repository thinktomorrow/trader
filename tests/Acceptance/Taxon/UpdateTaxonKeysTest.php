<?php
declare(strict_types=1);

namespace Tests\Acceptance\Taxon;

use Tests\Acceptance\TestCase;
use Thinktomorrow\Trader\Application\Taxon\UpdateTaxonKeys;
use Thinktomorrow\Trader\Testing\Support\Catalog;

class UpdateTaxonKeysTest extends TestCase
{
    public function test_it_can_update_keys(): void
    {
        $catalog = Catalog::inMemory();

        $taxon = $catalog->createTaxon();

        $catalog->apps->taxonApplication()->updateTaxonKeys(new UpdateTaxonKeys(
            $taxon->taxonId->get(),
            ['nl' => 'new-key-nl', 'fr' => 'new-key-fr'],
        ));

        $updatedTaxon = $catalog->repos->taxonRepository()->find($taxon->taxonId);

        $this->assertCount(2, $updatedTaxon->getTaxonKeys());
        $this->assertEquals('new-key-nl', $updatedTaxon->getTaxonKeys()[0]->taxonKeyId);
        $this->assertEquals('new-key-fr', $updatedTaxon->getTaxonKeys()[1]->taxonKeyId);
    }

    public function test_key_is_unique_per_locale(): void
    {
        $catalog = Catalog::inMemory();

        $taxon = $catalog->createTaxon();

        $catalog->apps->taxonApplication()->updateTaxonKeys(new UpdateTaxonKeys(
            $taxon->taxonId->get(),
            ['nl' => 'new-key-xxx', 'fr' => 'new-key-xxx'],
        ));

        $updatedTaxon = $catalog->repos->taxonRepository()->find($taxon->taxonId);

        $this->assertCount(2, $updatedTaxon->getTaxonKeys());
        $this->assertEquals('new-key-xxx', $updatedTaxon->getTaxonKeys()[0]->taxonKeyId);
        $this->assertEquals('new-key-xxx', $updatedTaxon->getTaxonKeys()[1]->taxonKeyId);
    }
}
