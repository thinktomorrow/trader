<?php

namespace Tests\Infrastructure\Vine;

use Illuminate\Support\Facades\DB;
use Thinktomorrow\Trader\Domain\Model\Taxon\Taxon;
use Thinktomorrow\Trader\Domain\Model\Taxon\TaxonId;
use Thinktomorrow\Trader\Domain\Model\Taxon\TaxonKey;
use Thinktomorrow\Trader\Infrastructure\Laravel\Repositories\MysqlTaxonRepository;
use Thinktomorrow\Trader\Infrastructure\Test\Repositories\InMemoryTaxonRepository;

trait TaxonHelpers
{
    protected function createDefaultTaxons()
    {
        $taxon = Taxon::create(TaxonId::fromString('first'), TaxonKey::fromString('taxon-first'));
        $taxon->addData(['title' => 'Taxon first']);
        $taxon->changeOrder(0);
        $this->createTaxon($taxon, ['aaa']);

        $taxon2 = Taxon::create(TaxonId::fromString('second'), TaxonKey::fromString('taxon-second'), TaxonId::fromString('first'));
        $taxon2->addData(['title' => 'Taxon second']);
        $taxon2->changeOrder(1);
        $this->createTaxon($taxon2, ['bbb']);

        $taxon3 = Taxon::create(TaxonId::fromString('third'), TaxonKey::fromString('taxon-third'), TaxonId::fromString('first'));
        $taxon3->addData(['title' => 'Taxon third']);
        $taxon3->changeOrder(2);
        $this->createTaxon($taxon3, ['ccc']);

        $taxon4 = Taxon::create(TaxonId::fromString('fourth'), TaxonKey::fromString('taxon-fourth'), TaxonId::fromString('third'));
        $taxon4->addData(['title' => 'Taxon fourth']);
        $taxon4->changeOrder(3);
        $this->createTaxon($taxon4, ['ddd']);

        $taxon5 = Taxon::create(TaxonId::fromString('fifth'), TaxonKey::fromString('taxon-fifth'));
        $taxon5->addData(['title' => 'Taxon fifth']);
        $taxon5->changeOrder(4);
        $this->createTaxon($taxon5, ['eee']);

        $taxon6 = Taxon::create(TaxonId::fromString('sixth'), TaxonKey::fromString('taxon-sixth'), TaxonId::fromString('fifth'));
        $taxon6->addData(['title' => 'Taxon sixth']);
        $taxon6->changeOrder(5);
        $this->createTaxon($taxon6, ['fff']);
    }

    private function createTaxon(Taxon $taxon, array $productIds = [])
    {
        foreach ($this->entityRepositories() as $taxonRepository) {

            // In memory
            if ($taxonRepository instanceof InMemoryTaxonRepository) {
                $taxonRepository->setProductIds($taxon->taxonId, $productIds);
            }
            // Mysql
            else {
                foreach ($productIds as $productId) {
                    DB::table('trader_taxa_products')->insert([
                        ['taxon_id' => $taxon->taxonId->get(), 'product_id' => $productId],
                    ]);
                }
            }

            $taxonRepository->save($taxon);
        }
    }

    private function entityRepositories(): \Generator
    {
        yield new InMemoryTaxonRepository();
        yield new MysqlTaxonRepository();
    }
}
