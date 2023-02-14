<?php

namespace Tests\Infrastructure\Vine;

use Illuminate\Support\Facades\DB;
use Thinktomorrow\Trader\Domain\Common\Locale;
use Thinktomorrow\Trader\Domain\Model\Product\Exceptions\CouldNotFindProduct;
use Thinktomorrow\Trader\Domain\Model\Product\Product;
use Thinktomorrow\Trader\Domain\Model\Product\ProductId;
use Thinktomorrow\Trader\Domain\Model\Product\ProductState;
use Thinktomorrow\Trader\Domain\Model\Taxon\Taxon;
use Thinktomorrow\Trader\Domain\Model\Taxon\TaxonId;
use Thinktomorrow\Trader\Domain\Model\Taxon\TaxonKey;
use Thinktomorrow\Trader\Domain\Model\Taxon\TaxonKeyId;
use Thinktomorrow\Trader\Infrastructure\Laravel\Repositories\MysqlProductRepository;
use Thinktomorrow\Trader\Infrastructure\Laravel\Repositories\MysqlTaxonRepository;
use Thinktomorrow\Trader\Infrastructure\Laravel\Repositories\MysqlVariantRepository;
use Thinktomorrow\Trader\Infrastructure\Test\Repositories\InMemoryTaxonRepository;
use Thinktomorrow\Trader\Infrastructure\Test\TestContainer;

trait TaxonHelpers
{
    protected function createDefaultTaxons()
    {
        $taxon = Taxon::create(TaxonId::fromString('first'));
        $taxon->updateTaxonKeys([
            TaxonKey::create($taxon->taxonId, TaxonKeyId::fromString('taxon-first'), Locale::fromString('nl')),
            TaxonKey::create($taxon->taxonId, TaxonKeyId::fromString('taxon-first-fr'), Locale::fromString('fr')),
//            TaxonKey::create($taxon->taxonId, TaxonKeyId::fromString('taxon-first-fr'), Locale::fromString('en')),
        ]);
        $taxon->addData(['title' => 'Taxon first']);
        $taxon->changeOrder(0);
        $this->createTaxon($taxon, ['aaa']);

        $taxon2 = Taxon::create(TaxonId::fromString('second'), TaxonId::fromString('first'));
        $taxon2->updateTaxonKeys([TaxonKey::create($taxon2->taxonId, TaxonKeyId::fromString('taxon-second'), Locale::fromString('nl'))]);
        $taxon2->addData(['title' => 'Taxon second']);
        $taxon2->changeOrder(1);
        $this->createTaxon($taxon2, ['bbb']);

        $taxon3 = Taxon::create(TaxonId::fromString('third'), TaxonId::fromString('first'));
        $taxon3->updateTaxonKeys([TaxonKey::create($taxon3->taxonId, TaxonKeyId::fromString('taxon-third'), Locale::fromString('nl'))]);
        $taxon3->addData(['title' => 'Taxon third']);
        $taxon3->changeOrder(2);
        $this->createTaxon($taxon3, ['ccc']);

        $taxon4 = Taxon::create(TaxonId::fromString('fourth'), TaxonId::fromString('third'));
        $taxon4->updateTaxonKeys([TaxonKey::create($taxon4->taxonId, TaxonKeyId::fromString('taxon-fourth'), Locale::fromString('nl'))]);
        $taxon4->addData(['title' => 'Taxon fourth']);
        $taxon4->changeOrder(3);
        $this->createTaxon($taxon4, ['ddd']);

        $taxon5 = Taxon::create(TaxonId::fromString('fifth'));
        $taxon5->updateTaxonKeys([TaxonKey::create($taxon5->taxonId, TaxonKeyId::fromString('taxon-fifth'), Locale::fromString('nl'))]);
        $taxon5->addData(['title' => 'Taxon fifth']);
        $taxon5->changeOrder(4);
        $this->createTaxon($taxon5, ['eee']);

        $taxon6 = Taxon::create(TaxonId::fromString('sixth'), TaxonId::fromString('fifth'));
        $taxon6->updateTaxonKeys([TaxonKey::create($taxon6->taxonId, TaxonKeyId::fromString('taxon-sixth'), Locale::fromString('nl'))]);
        $taxon6->addData(['title' => 'Taxon sixth']);
        $taxon6->changeOrder(5);
        $this->createTaxon($taxon6, ['fff']);
    }

    private function createTaxon(Taxon $taxon, array $productIds = [])
    {
        foreach ($this->entityRepositories() as $taxonRepository) {
            $taxonRepository->save($taxon);

            // In memory
            if ($taxonRepository instanceof InMemoryTaxonRepository) {
                $taxonRepository->setProductIds($taxon->taxonId, $productIds);
            }
            // Mysql
            else {
                foreach ($productIds as $productId) {
                    try {
                        $this->mysqlProductRepository()->find(ProductId::fromString($productId));
                    } catch(CouldNotFindProduct $e) {
                        $product = Product::create(ProductId::fromString($productId));
                        $this->mysqlProductRepository()->save($product);
                    }

                    DB::table('trader_taxa_products')->insert([
                        ['taxon_id' => $taxon->taxonId->get(), 'product_id' => $productId],
                    ]);
                }
            }
        }
    }

    private function entityRepositories(): \Generator
    {
        yield new InMemoryTaxonRepository();
        yield new MysqlTaxonRepository();
    }

    private function mysqlProductRepository()
    {
        return new MysqlProductRepository(new MysqlVariantRepository(new TestContainer()));
    }
}
