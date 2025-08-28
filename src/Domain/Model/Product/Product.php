<?php
declare(strict_types=1);

namespace Thinktomorrow\Trader\Domain\Model\Product;

use Thinktomorrow\Trader\Domain\Common\Entity\Aggregate;
use Thinktomorrow\Trader\Domain\Common\Entity\HasData;
use Thinktomorrow\Trader\Domain\Common\Event\RecordsEvents;
use Thinktomorrow\Trader\Domain\Model\Product\Events\ProductCreated;
use Thinktomorrow\Trader\Domain\Model\Product\Events\ProductDataUpdated;
use Thinktomorrow\Trader\Domain\Model\Product\Personalisation\HasPersonalisations;
use Thinktomorrow\Trader\Domain\Model\Product\Personalisation\Personalisation;
use Thinktomorrow\Trader\Domain\Model\Product\ProductTaxa\HasProductTaxa;
use Thinktomorrow\Trader\Domain\Model\Product\ProductTaxa\ProductTaxon;
use Thinktomorrow\Trader\Domain\Model\Product\ProductTaxa\VariantProperty;
use Thinktomorrow\Trader\Domain\Model\Product\Variant\HasVariants;
use Thinktomorrow\Trader\Domain\Model\Product\Variant\Variant;
use Thinktomorrow\Trader\Domain\Model\Taxonomy\TaxonomyType;

class Product implements Aggregate
{
    use RecordsEvents;
    use HasPersonalisations;
    use HasVariants;
    use HasProductTaxa;
    use HasData {
        addData as defaultAddData;
    }

    private ProductState $state;

    public readonly ProductId $productId;

    private function __construct()
    {
    }

    public static function create(ProductId $productId): static
    {
        $product = new static();
        $product->productId = $productId;
        $product->state = ProductState::offline;

        $product->recordEvent(new ProductCreated($product->productId));

        return $product;
    }

    public function updateState(ProductState $state): void
    {
        $this->state = $state;
    }

    public function getState(): ProductState
    {
        return $this->state;
    }

    public function addData(array $data): void
    {
        $this->defaultAddData($data);

        $this->recordEvent(new ProductDataUpdated($this->productId));
    }

    public function getMappedData(): array
    {
        return [
            'product_id' => $this->productId->get(),
            'state' => $this->state->value,
            'data' => json_encode($this->data),
        ];
    }

    public function getChildEntities(): array
    {
        return [
            Variant::class => array_map(fn (Variant $variant) => $variant->getMappedData(), $this->variants),
            ProductTaxon::class => array_map(
                fn (ProductTaxon $prop) => array_merge($prop->getMappedData()),
                array_values($this->productTaxa),
            ),
            Personalisation::class => array_map(
                fn (Personalisation $personalisation) => $personalisation->getMappedData(),
                array_values($this->personalisations)
            ),
        ];
    }

    public static function fromMappedData(array $state, array $childEntities = []): static
    {
        $product = new static();
        $product->productId = ProductId::fromString($state['product_id']);
        $product->state = ProductState::from($state['state']);

        if (array_key_exists(Variant::class, $childEntities)) {
            $product->variants = array_map(fn ($variantState) => Variant::fromMappedData($variantState[0], $state, $variantState[1]), $childEntities[Variant::class]);
        }

        if (array_key_exists(ProductTaxon::class, $childEntities)) {
            foreach ($childEntities[ProductTaxon::class] as $childState) {
                $product->productTaxa[] = (isset($childState['taxonomy_type']) && $childState['taxonomy_type'] == TaxonomyType::variant_property->value)
                    ? VariantProperty::fromMappedData($childState, $state)
                    : ProductTaxon::fromMappedData($childState, $state);
            }
        }

        if (array_key_exists(Personalisation::class, $childEntities)) {
            foreach ($childEntities[Personalisation::class] as $personalisationState) {
                $product->personalisations[] = Personalisation::fromMappedData($personalisationState, $state);
            }
        }

        $product->data = json_decode($state['data'], true);

        return $product;
    }
}
