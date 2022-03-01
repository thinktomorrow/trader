<?php

namespace Tests\Infrastructure\Vine;

use Illuminate\Support\Facades\DB;
use Thinktomorrow\Trader\Domain\Model\Taxon\Taxon;
use Thinktomorrow\Trader\Infrastructure\Test\InMemoryTaxonRepository;

trait CreatesTaxon
{
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
