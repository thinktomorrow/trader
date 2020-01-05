<?php

namespace Thinktomorrow\Trader\Find\Catalog\Ports\Laravel;

use Illuminate\Support\Facades\DB;
use Illuminate\Contracts\Container\Container;
use Thinktomorrow\Trader\Find\Catalog\Reads\Product;
use Thinktomorrow\Trader\Find\Catalog\Reads\ProductFactory;

abstract class AbstractDbProductRepository
{
    /** @var Container */
    protected $container;

    /** @var ProductFactory */
    protected $productFactory;

    /** @var string */
    protected $channel;

    /** @var string */
    protected $locale;

    public function __construct(Container $container, ProductFactory $productFactory)
    {
        $this->productFactory = $productFactory;
        $this->container = $container;

        // TODO: set default channel and locale
    }

    public function channel(string $channel)
    {
        // TODO: might need to sluggify this channel input
        $this->channel = $channel;

        return $this;
    }

    public function locale(string $locale)
    {
        $this->locale = $locale;

        return $this;
    }

    protected function initBuilder()
    {
        $tableName = $this->tableName();

        // TODO need better query where missing locale record should not return empty sql result
        return DB::table($tableName)
            ->leftjoin('product_read_translations', $tableName . '.id', '=', 'product_read_translations.product_id')
            ->where('product_read_translations.locale', $this->locale)
            ->select($tableName . '.*');
    }

    protected function tableName(): string
    {
        $tableName = 'product_reads';

        if (isset($this->channel)) {
            $tableName .= "_$this->channel";
        }

        return $tableName;
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
            'id'   => $record->id,
            'data' => (array)json_decode($record->data),
        ]);
    }
}
