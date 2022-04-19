<?php

namespace Tests\Infrastructure\Vine;

use Illuminate\Support\Facades\DB;
use Thinktomorrow\Trader\Domain\Model\Taxon\Taxon;
use Thinktomorrow\Trader\Domain\Model\Taxon\TaxonId;
use Thinktomorrow\Trader\Infrastructure\Test\Repositories\InMemoryTaxonRepository;

trait TaxonHelpers
{
    protected function createDefaultTaxons()
    {
        $taxon = Taxon::create(TaxonId::fromString('first'), 'taxon-first', ['label' => 'Taxon first']);
        $taxon->changeOrder(0);
        $this->createTaxon($taxon, ['aaa']);

        $taxon2 = Taxon::create(TaxonId::fromString('second'), 'taxon-second', ['label' => 'Taxon second'], TaxonId::fromString('first'));
        $taxon2->changeOrder(1);
        $this->createTaxon($taxon2, ['bbb']);

        $taxon3 = Taxon::create(TaxonId::fromString('third'), 'taxon-third', ['label' => 'Taxon third'], TaxonId::fromString('first'));
        $taxon3->changeOrder(2);
        $this->createTaxon($taxon3, ['ccc']);

        $taxon4 = Taxon::create(TaxonId::fromString('fourth'), 'taxon-fourth', ['label' => 'Taxon fourth'], TaxonId::fromString('third'));
        $taxon4->changeOrder(3);
        $this->createTaxon($taxon4, ['ddd']);

        $taxon5 = Taxon::create(TaxonId::fromString('fifth'), 'taxon-fifth', ['label' => 'Taxon fifth']);
        $taxon5->changeOrder(4);
        $this->createTaxon($taxon5, ['eee']);

        $taxon6 = Taxon::create(TaxonId::fromString('sixth'), 'taxon-sixth', ['label' => 'Taxon sixth'], TaxonId::fromString('fifth'));
        $taxon6->changeOrder(5);
        $this->createTaxon($taxon6, ['fff']);
    }

    private function createTaxon(Taxon $taxon, array $productIds = [])
    {
        foreach($this->repositories() as $taxonRepository) {

            // In memory
            if($taxonRepository instanceof InMemoryTaxonRepository) {
                $taxonRepository->setProductIds($taxon->taxonId, $productIds);
            }
            // Mysql
            else {
                foreach($productIds as $productId) {
                    DB::table('trader_taxa_products')->insert([
                        ['taxon_id' => $taxon->taxonId->get(), 'product_id' => $productId],
                    ]);
                }
            }

            $taxonRepository->save($taxon);
        }
    }
}
