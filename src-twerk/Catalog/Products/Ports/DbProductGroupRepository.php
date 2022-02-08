<?php
declare(strict_types=1);

namespace Thinktomorrow\Trader\Catalog\Products\Ports;

use Thinktomorrow\Trader\Catalog\Products\Domain\ProductGroupState;
use Illuminate\Contracts\Container\Container;
use Illuminate\Support\Collection;
use Thinktomorrow\Trader\Catalog\Options\Domain\OptionRepository;
use Thinktomorrow\Trader\Catalog\Options\Ports\Options;
use Thinktomorrow\Trader\Catalog\Products\Domain\ProductGroup;
use Thinktomorrow\Trader\Catalog\Products\Domain\ProductGroupRepository;
use Thinktomorrow\Trader\Catalog\Products\Domain\ProductRepository;
use Thinktomorrow\Trader\Catalog\Taxa\Domain\TaxonRepository;
use Thinktomorrow\Trader\Catalog\Taxa\Ports\TaxonModel;

class DbProductGroupRepository implements ProductGroupRepository
{
    private Container $container;
    private ProductRepository $productRepository;
    private TaxonRepository $taxonRepository;

    public function __construct(Container $container, ProductRepository $productRepository, TaxonRepository $taxonRepository)
    {
        $this->container = $container;
        $this->productRepository = $productRepository;
        $this->taxonRepository = $taxonRepository;

        // TODO: set default channel and locale
    }

    // TODO(Ben): all() should be replaced since we dont want to fetch ALL products, rather use a async search by keyword or taxon instead...
    public function all(): Collection
    {
        return ProductGroupModel::all()->map(fn ($item) => $this->composeProductGroup($item));
    }

    public function findById(string $productGroupId): ProductGroup
    {
        $model = ProductGroupModel::findOrFail($productGroupId);

        return $this->composeProductGroup($model);
    }

    public function findByProductId(string $productId): ProductGroup
    {
        $model = ProductGroupModel::findByProductId($productId);

        return $this->composeProductGroup($model);
    }

    public function create(array $values): ProductGroup
    {
        $model = ProductGroupModel::create($values);

        return $this->composeProductGroup($model);
    }

    public function syncTaxonomy(string $productGroupId, array $taxonKeys): void
    {
        $model = ProductGroupModel::find($productGroupId);
        $taxaIds = TaxonModel::whereIn('key', $taxonKeys)->select('id')->get()->pluck('id')->toArray();

        $model->taxa()->sync($taxaIds);
    }

    public function save(ProductGroup $productGroup): void
    {
        // TODO: Implement save() method.
    }

    public function delete(ProductGroup $productGroup): void
    {
        // TODO: Implement delete() method.
    }

    public function composeProductGroup(ProductGroupModel $productGroupModel): ProductGroup
    {
        $taxonNodes = $productGroupModel->taxa->map(fn ($taxonModel) => $this->taxonRepository->findById((string)$taxonModel->id));

        $options = new Options(
            ...$productGroupModel->options->map(fn ($model) => app(OptionRepository::class)::compose($model))->all()
        );

        return $this->container->make(ProductGroup::class, [
            'id' => (string)$productGroupModel->id,
            'products' => $productGroupModel->products->map(fn ($productModel) => $this->productRepository->composeProduct($productModel)),

            // Specific subset of the grid products for this productgroup. This is used when grid filtering is in effect. when null is passed, all grid products will be shown
            'gridProductIds' => $productGroupModel->grid_product_ids ? explode(',', $productGroupModel->grid_product_ids) : null,
            'taxonomy' => $taxonNodes,
            'options' => $options,
            'data' => array_merge($productGroupModel->rawDynamicValues(), [
                'images' => $productGroupModel->assets('productgroup-images'),
                'is_visitable' => in_array($productGroupModel->getProductGroupState(), DefaultProductGroupStateMachine::getOnlineStates()),
            ]),
        ]);
    }
}
