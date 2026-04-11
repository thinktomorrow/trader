<?php
declare(strict_types=1);

namespace Tests\Infrastructure\Repositories;

use Tests\Infrastructure\TestCase;
use Thinktomorrow\Trader\Domain\Model\Taxon\Exceptions\CouldNotFindTaxon;
use Thinktomorrow\Trader\Domain\Model\Taxon\TaxonId;
use Thinktomorrow\Trader\Testing\Catalog\CatalogContext;

final class TaxonRepositoryTest extends TestCase
{
    public function test_it_can_save_and_find_a_taxon()
    {
        /** @var CatalogContext $catalog */
        foreach (CatalogContext::drivers() as $catalog) {
            $repository = $catalog->repos()->taxonRepository();

            $taxon = $catalog->dontPersist()->createTaxon();

            $repository->save($taxon);
            $taxon->releaseEvents();

            $this->assertEquals($taxon, $repository->find($taxon->taxonId));
        }
    }

    public function test_it_can_save_and_find_a_taxon_by_key()
    {
        /** @var CatalogContext $catalog */
        foreach (CatalogContext::drivers() as $catalog) {
            $repository = $catalog->repos()->taxonRepository();

            $taxon = $catalog->dontPersist()->createTaxon();

            $repository->save($taxon);
            $taxon->releaseEvents();

            $freshTaxon = $repository->find($taxon->taxonId);

            $this->assertTrue(count($freshTaxon->getTaxonKeys()) > 0);

            foreach ($freshTaxon->getTaxonKeys() as $taxonKey) {
                $this->assertEquals($taxon, $repository->findByKey($taxonKey->getKey()));
            }
        }
    }

    public function test_it_can_save_and_find_many()
    {
        /** @var CatalogContext $catalog */
        foreach (CatalogContext::drivers() as $catalog) {
            $repository = $catalog->repos()->taxonRepository();

            $taxon = $catalog->dontPersist()->createTaxon();

            $repository->save($taxon);
            $taxon->releaseEvents();

            $freshTaxa = $repository->findMany([$taxon->taxonId->get()]);

            $this->assertTrue(count($freshTaxa) === 1);
        }
    }

    public function test_it_can_delete_a_taxon()
    {
        $taxonsNotFound = 0;

        /** @var CatalogContext $catalog */
        foreach (CatalogContext::drivers() as $catalog) {
            $repository = $catalog->repos()->taxonRepository();

            $taxon = $catalog->createTaxon();

            $repository->delete($taxon->taxonId);

            try {
                $repository->find($taxon->taxonId);
            } catch (CouldNotFindTaxon $e) {
                $taxonsNotFound++;
            }
        }

        $this->assertCount($taxonsNotFound, CatalogContext::drivers());
    }

    public function test_it_preserves_input_order_when_finding_many_taxa(): void
    {
        /** @var CatalogContext $catalog */
        foreach (CatalogContext::drivers() as $catalog) {
            $repository = $catalog->repos()->taxonRepository();

            $suffix = uniqid();
            $taxonomy = $catalog->createTaxonomy('taxonomy-'.$suffix);

            $taxonA = $catalog->createTaxon('taxon-a-'.$suffix, $taxonomy->taxonomyId->get());
            $taxonB = $catalog->createTaxon('taxon-b-'.$suffix, $taxonomy->taxonomyId->get());
            $taxonC = $catalog->createTaxon('taxon-c-'.$suffix, $taxonomy->taxonomyId->get());

            $inputOrder = [
                $taxonC->taxonId->get(),
                $taxonA->taxonId->get(),
                $taxonB->taxonId->get(),
            ];

            $freshTaxa = $repository->findMany($inputOrder);

            $this->assertSame(
                $inputOrder,
                array_map(fn ($taxon) => $taxon->taxonId->get(), $freshTaxa)
            );
        }
    }

    public function test_it_can_generate_a_next_reference()
    {
        /** @var CatalogContext $catalog */
        foreach (CatalogContext::drivers() as $catalog) {
            $repository = $catalog->repos()->taxonRepository();

            $this->assertInstanceOf(TaxonId::class, $repository->nextReference());
        }
    }
}
