<?php

declare(strict_types=1);

namespace Tests\Acceptance\Taxon;

use Tests\Acceptance\TestCase;
use Thinktomorrow\Trader\Application\Taxon\UpdateTaxonKeys;

class UpdateTaxonKeysTest extends TestCase
{
    public function test_it_can_update_keys(): void
    {
        $taxon = $this->catalogContext->createTaxon();

        $this->catalogContext->apps()->taxonApplication()->updateTaxonKeys(new UpdateTaxonKeys(
            $taxon->taxonId->get(),
            ['nl' => 'new-key-nl', 'fr' => 'new-key-fr'],
        ));

        $updatedTaxon = $this->catalogContext->repos()->taxonRepository()->find($taxon->taxonId);

        $this->assertCount(2, $updatedTaxon->getTaxonKeys());
        $this->assertEquals('new-key-fr', $updatedTaxon->getTaxonKeys()[0]->getKey());
        $this->assertEquals('new-key-nl', $updatedTaxon->getTaxonKeys()[1]->getKey());
    }

    public function test_key_is_unique_per_locale(): void
    {
        $taxon = $this->catalogContext->createTaxon();

        $this->catalogContext->apps()->taxonApplication()->updateTaxonKeys(new UpdateTaxonKeys(
            $taxon->taxonId->get(),
            ['nl' => 'new-key-xxx', 'fr' => 'new-key-xxx'],
        ));

        $updatedTaxon = $this->catalogContext->repos()->taxonRepository()->find($taxon->taxonId);

        $this->assertCount(2, $updatedTaxon->getTaxonKeys());
        $this->assertEquals('new-key-xxx', $updatedTaxon->getTaxonKeys()[0]->getKey());
        $this->assertEquals('new-key-xxx', $updatedTaxon->getTaxonKeys()[1]->getKey());
    }
}
