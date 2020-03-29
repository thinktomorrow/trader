<?php

namespace Thinktomorrow\Trader\Find\Catalog\Ports\Laravel;

use Assert\Assertion;
use Thinktomorrow\Trader\Common\Cash\Cash;
use Illuminate\Contracts\Container\Container;
use Thinktomorrow\Trader\Find\Channels\ChannelId;
use Thinktomorrow\Trader\Common\Domain\Taxes\TaxRate;
use Thinktomorrow\Trader\Find\Catalog\Domain\ProductId;
use Thinktomorrow\Trader\Common\Domain\Locales\LocaleId;
use Thinktomorrow\Trader\Find\Catalog\Reads\ProductRead;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Thinktomorrow\Trader\Find\Catalog\Reads\CatalogRepository;
use Thinktomorrow\Trader\Find\Catalog\Reads\ProductReadFactory;
use Thinktomorrow\Trader\Find\Catalog\Reads\ProductReadCollection;

class DbCatalogRepository extends AbstractDbProductRepository implements CatalogRepository
{
    /** @var ChannelId */
    protected $channelId;

    /** @var LocaleId */
    protected $localeId;

    /** @var Container */
    protected $container;

    /** @var ProductReadFactory */
    protected $productReadFactory;

    /** @var array */
    protected $builderParts = [];

    /**
     * Paginate the results. This will return a Laravel LengthAwarePaginator object
     * @var bool
     */
    private $paginate = false;

    /**
     * In case of paginated results, this will set the item amount per page.
     * @var int
     */
    private $perPage = 16;

    public function __construct(Container $container, ProductReadFactory $productReadFactory)
    {
        $this->productReadFactory = $productReadFactory;
        $this->container = $container;

        // TODO: set default channel and locale
    }

    public function channel(ChannelId $channelId)
    {
        // TODO: might need to sluggify this channel input
        $this->channelId = $channelId;

        return $this;
    }

    public function locale(LocaleId $localeId)
    {
        $this->localeId = $localeId;

        return $this;
    }

    public function paginate(int $perPage): CatalogRepository
    {
        $this->paginate = true;
        $this->perPage = $perPage;

        return $this;
    }

    public function sortByPrice(): CatalogRepository
    {
        return $this->addToBuilder('orderBy', ['saleprice', 'ASC']);
    }

    public function sortByPriceDesc(): CatalogRepository
    {
        return $this->addToBuilder('orderBy', ['saleprice', 'DESC']);
    }

    protected function addToBuilder($method, array $parameters): DbCatalogRepository
    {
        $this->builderParts[] = [
            'method' => $method,
            'parameters' => $parameters,
        ];

        return $this;
    }

    public function getAll(): LengthAwarePaginator
    {
        $this->assertChannelAndLocaleAreSet();

        $builder = $this->initBuilder($this->channelId, $this->localeId);

        foreach($this->builderParts as $part) {
            $builder->{$part['method']}(...$part['parameters']);
        }

        /** @var LengthAwarePaginator $result */
        $result = $builder->paginate($this->perPage);

        $adjusterInstances = $this->getAdjusterInstances();

        $itemInstances = $result->map(function($record) use($adjusterInstances){
            return $this->productReadFactory->create(
                $this->createProductReadFromRecord($record),
                $adjusterInstances
            );
        });

        return $result->setCollection($itemInstances);
    }

    public function findById($id): ProductRead
    {
        $this->assertChannelAndLocaleAreSet();

        $record = $this->initBuilder($this->channelId, $this->localeId)->where($this->tableName($this->channelId->get()).'.id', $id)->first();
        Assertion::notNull($record, "No product found by id [$id] in table [{$this->tableName($this->channelId->get())}]");

        $productRead = $this->createProductReadFromRecord($record);

        return $this->productReadFactory->create($productRead, $this->getAdjusterInstances());
    }

    private function assertChannelAndLocaleAreSet()
    {
        if(!isset($this->channelId)){
            throw new \InvalidArgumentException('Missing required channel value for repository. Please use method channel() to add the channel');
        }

        if(!isset($this->localeId)){
            throw new \InvalidArgumentException('Missing required locale value for repository. Please use method locale() to add the locale');
        }
    }

    protected function getAdjusterInstances(): array
    {
        return array_map(function (string $adjusterClass) {
            return $this->container->make($adjusterClass);
        }, $this->productReadFactory->getAdjusters());
    }

    protected function createProductReadFromRecord(object $record): ProductRead
    {
        return $this->container->makeWith(ProductRead::class, [
            'channelId' => $this->channelId,
            'localeId' => $this->localeId,
            'id'   => ProductId::fromString($record->id),
            'salePrice' => Cash::make($record->saleprice),
            'taxRate' => TaxRate::fromPercent($record->taxrate),
            'attributes' => array_merge((array) $record, (array) json_decode($record->data)),
        ]);
    }
}
