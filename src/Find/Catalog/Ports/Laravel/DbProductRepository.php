<?php

namespace Thinktomorrow\Trader\Find\Catalog\Ports\Laravel;

use Assert\Assertion;
use Thinktomorrow\Trader\Common\Cash\Cash;
use Illuminate\Contracts\Container\Container;
use Thinktomorrow\Trader\Find\Channels\ChannelId;
use Thinktomorrow\Trader\Find\Catalog\Domain\Product;
use Thinktomorrow\Trader\Common\Domain\Taxes\TaxRate;
use Thinktomorrow\Trader\Find\Catalog\Domain\ProductId;
use Thinktomorrow\Trader\Common\Domain\Locales\LocaleId;
use Thinktomorrow\Trader\Find\Catalog\Domain\ProductFactory;
use Thinktomorrow\Trader\Find\Catalog\Domain\ProductRepository;

class DbProductRepository extends AbstractDbProductRepository implements ProductRepository
{
    /** @var Container */
    protected $container;

    /** @var ProductFactory */
    protected $productFactory;

    public function __construct(Container $container, ProductFactory $productReadFactory)
    {
        $this->productFactory = $productReadFactory;
        $this->container = $container;

        // TODO: set default channel and locale
    }

    public function findById(ProductId $productId, ChannelId $channel, LocaleId $locale): Product
    {
        $record = $this->initBuilder($channel, $locale)->where($this->tableName($channel).'.id', $productId->get())->first();
        Assertion::notNull($record, "No product found by id [{$productId->get()}] in table [{$this->tableName($channel)}]");

        $product = $this->createProductFromRecord($record);

        return $this->productFactory->create($product, $this->getAdjusterInstances());
    }

    protected function getAdjusterInstances(): array
    {
        return array_map(function (string $adjusterClass) {
            return $this->container->make($adjusterClass);
        }, $this->productFactory->getAdjusters());
    }

    protected function createProductFromRecord(object $record): Product
    {
        return $this->container->makeWith(Product::class, [
            'id'   => ProductId::fromString($record->id),
            'salePrice' => Cash::make($record->saleprice),
            'taxRate' => TaxRate::fromPercent($record->taxrate),
            'attributes' => array_merge((array) $record, (array) json_decode($record->data)),
        ]);
    }
}
