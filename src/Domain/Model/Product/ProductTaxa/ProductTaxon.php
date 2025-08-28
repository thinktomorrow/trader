<?php
declare(strict_types=1);

namespace Thinktomorrow\Trader\Domain\Model\Product\ProductTaxa;

use Thinktomorrow\Trader\Domain\Common\Entity\ChildEntity;
use Thinktomorrow\Trader\Domain\Common\Entity\HasData;
use Thinktomorrow\Trader\Domain\Model\Product\ProductId;
use Thinktomorrow\Trader\Domain\Model\Taxon\TaxonId;
use Thinktomorrow\Trader\Domain\Model\Taxon\TaxonState;

class ProductTaxon implements ChildEntity
{
    use HasData;

    public readonly ProductId $productId;
    public readonly TaxonId $taxonId;
    private TaxonState $state;

    private function __construct()
    {
    }

    public static function create(ProductId $productId, TaxonId $taxonId): static
    {
        $object = new static();

        $object->productId = $productId;
        $object->taxonId = $taxonId;
        $object->state = TaxonState::online;

        return $object;
    }

    public function toVariantProperty(): VariantProperty
    {
        $variantProperty = new VariantProperty();

        $variantProperty->productId = $this->productId;
        $variantProperty->taxonId = $this->taxonId;
        $variantProperty->state = $this->state;
        $variantProperty->data = $this->data;

        return $variantProperty;
    }

    public function changeState(TaxonState $state): void
    {
        $this->state = $state;
    }

    public function getState(): TaxonState
    {
        return $this->state;
    }

    public function getMappedData(): array
    {
        return [
            'product_id' => $this->productId->get(),
            'taxon_id' => $this->taxonId->get(),
            'state' => $this->state->value,
            'data' => json_encode($this->data),
        ];
    }

    public static function fromMappedData(array $state, array $aggregateState, array $childEntities = []): static
    {
        $object = new static();

        $object->productId = ProductId::fromString($aggregateState['product_id']);
        $object->taxonId = TaxonId::fromString($state['taxon_id']);
        $object->state = TaxonState::from($state['state']);
        $object->data = json_decode($state['data'], true);

        return $object;
    }
}
