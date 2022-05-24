<?php
declare(strict_types=1);

namespace Tests\Acceptance\Taxon;

use Tests\Infrastructure\Vine\TaxonHelpers;
use Thinktomorrow\Trader\Application\Taxon\CreateTaxon;

class CreateTaxonTest extends TaxonContext
{
    use TaxonHelpers;

    /** @test */
    public function it_can_create_a_taxon()
    {
        $taxonId = $this->taxonApplication->createTaxon(new CreateTaxon('taxon-key',['foo' => 'bar']));

        $taxon = $this->taxonRepository->find($taxonId);

        $this->assertEquals(['foo' => 'bar'], $taxon->getData());
        $this->assertEquals('taxon-key', $taxon->taxonKey->get());
        $this->assertNull($taxon->getParentId());
    }

    /** @test */
    public function it_can_create_a_nested_taxon()
    {
        $taxonRootId = $this->taxonApplication->createTaxon(new CreateTaxon('taxon-key-root',['foo' => 'bar']));
        $this->taxonRepository->setNextReference('abc');
        $taxonId = $this->taxonApplication->createTaxon(new CreateTaxon('taxon-key',['foo' => 'bar'], $taxonRootId->get()));

        $taxon = $this->taxonRepository->find($taxonId);

        $this->assertEquals(['foo' => 'bar'], $taxon->getData());
        $this->assertEquals('taxon-key', $taxon->taxonKey->get());
        $this->assertEquals($taxonRootId, $taxon->getParentId());
    }

    /** @test */
    public function it_creates_a_unique_key_reference()
    {
        $existingTaxonId = $this->taxonApplication->createTaxon(new CreateTaxon('taxon-key',['foo' => 'bar']));

        $this->taxonRepository->setNextReference('abc');
        $taxonId = $this->taxonApplication->createTaxon(new CreateTaxon('taxon-key',['foo' => 'bar']));

        $taxon = $this->taxonRepository->find($taxonId);

        $this->assertNotEquals('taxon-key', $taxon->taxonKey->get());
    }
}
