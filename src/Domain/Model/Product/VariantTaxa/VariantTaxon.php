<?php
declare(strict_types=1);

namespace Thinktomorrow\Trader\Domain\Model\Product\VariantTaxa;

use Thinktomorrow\Trader\Domain\Common\Entity\ChildEntity;
use Thinktomorrow\Trader\Domain\Common\Entity\HasData;
use Thinktomorrow\Trader\Domain\Model\Product\Variant\VariantId;
use Thinktomorrow\Trader\Domain\Model\Taxon\TaxonId;
use Thinktomorrow\Trader\Domain\Model\Taxon\TaxonState;

class VariantTaxon implements ChildEntity
{
    use HasData;

    public readonly VariantId $variantId;
    public readonly TaxonId $taxonId;
    private TaxonState $state;

    private function __construct()
    {
    }

    public static function create(VariantId $variantId, TaxonId $taxonId): static
    {
        $object = new static();

        $object->variantId = $variantId;
        $object->taxonId = $taxonId;
        $object->state = TaxonState::online;

        return $object;
    }

    public function toVariantProperty(): VariantProperty
    {
        $variantProperty = new VariantProperty();

        $variantProperty->variantId = $this->variantId;
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
            'variant_id' => $this->variantId->get(),
            'taxon_id' => $this->taxonId->get(),
            'state' => $this->state->value,
            'data' => json_encode($this->data),
        ];
    }

    public static function fromMappedData(array $state, array $aggregateState, array $childEntities = []): static
    {
        $object = new static();

        $object->variantId = VariantId::fromString($aggregateState['variant_id']);
        $object->taxonId = TaxonId::fromString($state['taxon_id']);
        $object->state = TaxonState::from($state['state']);
        $object->data = json_decode($state['data'], true);

        return $object;
    }
}
