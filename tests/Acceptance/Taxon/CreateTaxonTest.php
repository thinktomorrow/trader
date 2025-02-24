<?php
declare(strict_types=1);

namespace Tests\Acceptance\Taxon;

use Tests\Infrastructure\Vine\TaxonHelpers;
use Thinktomorrow\Trader\Application\Taxon\CreateTaxon;
use Thinktomorrow\Trader\Domain\Common\Locale;

class CreateTaxonTest extends TaxonContext
{
    use TaxonHelpers;

    public function test_it_can_create_a_taxon()
    {
        $taxonId = $this->taxonApplication->createTaxon(new CreateTaxon('taxon-key', 'nl', ['foo' => 'bar']));

        $taxon = $this->taxonRepository->find($taxonId);

        $this->assertEquals(['foo' => 'bar'], $taxon->getData());
        $this->assertEquals('taxon-key', $taxon->getTaxonKeys()[0]->taxonKeyId->get());
        $this->assertEquals(Locale::fromString('nl'), $taxon->getTaxonKeys()[0]->getLocale());
        $this->assertNull($taxon->getParentId());
    }

    public function test_it_can_create_a_nested_taxon()
    {
        $taxonRootId = $this->taxonApplication->createTaxon(new CreateTaxon('taxon-key-root', 'nl', ['foo' => 'bar']));
        $this->taxonRepository->setNextReference('abc');
        $taxonId = $this->taxonApplication->createTaxon(new CreateTaxon('taxon-key', 'nl', ['foo' => 'bar'], $taxonRootId->get()));

        $taxon = $this->taxonRepository->find($taxonId);

        $this->assertEquals(['foo' => 'bar'], $taxon->getData());
        $this->assertEquals('taxon-key', $taxon->getTaxonKeys()[0]->taxonKeyId->get());
        $this->assertEquals($taxonRootId, $taxon->getParentId());
    }

    public function test_it_creates_a_unique_key_reference()
    {
        $this->taxonApplication->createTaxon(new CreateTaxon('taxon-key', 'nl', ['foo' => 'bar']));

        $this->taxonRepository->setNextReference('abc');
        $taxonId = $this->taxonApplication->createTaxon(new CreateTaxon('taxon-key', 'nl', ['foo' => 'bar']));

        $taxon = $this->taxonRepository->find($taxonId);

        $this->assertNotEquals('taxon-key', $taxon->getTaxonKeys()[0]->taxonKeyId->get());
    }
}
