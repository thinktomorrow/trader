<?php
declare(strict_types=1);

namespace Thinktomorrow\Trader\Domain\Model\Product;

use Thinktomorrow\Trader\Domain\Common\Entity\Aggregate;
use Thinktomorrow\Trader\Domain\Common\Entity\HasData;
use Thinktomorrow\Trader\Domain\Common\Event\RecordsEvents;
use Thinktomorrow\Trader\Domain\Model\Product\Events\ProductCreated;
use Thinktomorrow\Trader\Domain\Model\Product\Events\ProductDataUpdated;
use Thinktomorrow\Trader\Domain\Model\Product\Option\Option;
use Thinktomorrow\Trader\Domain\Model\Product\Option\OptionValue;
use Thinktomorrow\Trader\Domain\Model\Product\Variant\Variant;
use Thinktomorrow\Trader\Domain\Model\Taxon\TaxonId;
use Thinktomorrow\Trader\Domain\Model\Product\Personalisation\Personalisation;

class Product implements Aggregate
{
    use RecordsEvents;
    use HasOptions;
    use HasPersonalisations;
    use HasVariants;
    use BelongsToTaxa;
    use HasData{
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
            'taxon_ids' => array_map(fn ($taxonId) => $taxonId->get(), $this->taxonIds),
            'data' => json_encode($this->data),
        ];
    }

    public function getChildEntities(): array
    {
        return [
            Variant::class => array_map(fn (Variant $variant) => $variant->getMappedData(), $this->variants),
            Option::class => array_map(
                fn (Option $option) =>
                array_merge($option->getMappedData(), ['values' => $option->getChildEntities()[OptionValue::class]]),
                array_values($this->options)
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
            $product->variants = array_map(fn ($variantState) => Variant::fromMappedData($variantState, $state), $childEntities[Variant::class]);
        }

        if (array_key_exists(Option::class, $childEntities)) {
            foreach ($childEntities[Option::class] as $optionState) {
                $product->options[] = Option::fromMappedData($optionState, $state, [OptionValue::class => $optionState['values']]);
            }
        }

        if (array_key_exists(Personalisation::class, $childEntities)) {
            foreach ($childEntities[Personalisation::class] as $personalisationState) {
                $product->personalisations[] = Personalisation::fromMappedData($personalisationState, $state);
            }
        }

        $product->data = json_decode($state['data'], true);
        $product->taxonIds = array_map(fn ($taxonId) => TaxonId::fromString($taxonId), $state['taxon_ids']);

        return $product;
    }
}
