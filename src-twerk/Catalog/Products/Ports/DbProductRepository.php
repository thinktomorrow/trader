<?php
declare(strict_types=1);

namespace Thinktomorrow\Trader\Catalog\Products\Ports;

use Illuminate\Contracts\Container\Container;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use Thinktomorrow\Trader\Catalog\Options\Domain\OptionRepository;
use Thinktomorrow\Trader\Catalog\Options\Ports\Options;
use Thinktomorrow\Trader\Catalog\Products\Domain\Product;
use Thinktomorrow\Trader\Catalog\Products\Domain\ProductRepository;
use Thinktomorrow\Trader\Common\Cash\Cash;
use Thinktomorrow\Trader\Taxes\TaxRate;

class DbProductRepository implements ProductRepository
{
    private Container $container;

    public function __construct(Container $container)
    {
        $this->container = $container;

        // TODO: set default channel and locale
    }

    public function findById(string $productId): Product
    {
        $model = ProductModel::findOrFail($productId);

        return $this->composeProduct($model);
    }

    public function getByProductGroup(string $productGroupId): Collection
    {
        $collection = ProductModel::where('productgroup_id', $productGroupId)->orderBy('order_column', 'ASC')->get();

        return $collection->map(fn ($model) => $this->composeProduct($model));
    }

    public function create(array $values): Product
    {
        $model = ProductModel::create(Arr::except($values, 'option_ids'));

        $model->options()->sync($values['option_ids']);

        return $this->composeProduct($model);
    }

    public function save(string $productId, array $values): void
    {
        $model = ProductModel::findOrFail($productId);

        $model->update(Arr::except($values, 'option_ids'));

        if (isset($values['option_ids'])) {
            $model->options()->sync($values['option_ids']);
        }
    }

    public function delete(string $productId): void
    {
        ProductModel::find($productId)->delete();
    }

    public function composeProduct(ProductModel $productModel): Product
    {
        return $this->container->make(Product::class, $this->productArguments($productModel));
    }

    protected function productArguments(ProductModel $productModel): array
    {
        $options = new Options(
            ...$productModel->options->map(fn ($model) => app(OptionRepository::class)::compose($model))->all()
        );

        return [
            'id' => (string)$productModel->id,
            'isGridProduct' => $productModel->is_grid_product,
            'productGroupId' => (string)$productModel->productgroup_id,
            'salePrice' => Cash::make($productModel->sale_price),
            'unitPrice' => Cash::make($productModel->unit_price),
            'taxRate' => TaxRate::fromInteger($productModel->tax_rate),
            'options' => $options,
            'data' => array_merge($productModel->rawDynamicValues(), [ // TODO: dynamicAttributes should not be in trader
                'images' => $productModel->assets('product-images'),
                'prices_include_tax' => $productModel->doPricesIncludeTax(),
                'available_state' => $productModel->getProductState(),
                'specs' => $productModel->specs,
            ]),
        ];
    }
}
