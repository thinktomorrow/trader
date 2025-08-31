<?php

namespace Thinktomorrow\Trader\Domain\Model\Taxonomy;

use Thinktomorrow\Trader\Domain\Common\Entity\Aggregate;
use Thinktomorrow\Trader\Domain\Common\Entity\HasData;
use Thinktomorrow\Trader\Domain\Common\Event\RecordsEvents;
use Thinktomorrow\Trader\Domain\Model\Taxonomy\Events\TaxonomyCreated;

class Taxonomy implements Aggregate
{
    use HasData;
    use RecordsEvents;

    public readonly TaxonomyId $taxonomyId;
    private TaxonomyType $type;
    private TaxonomyState $state;

    private bool $showsAsGridFilter;
    private bool $showsInGrid;
    private bool $allowsMultipleValues;
    private bool $allowsNestableValues;
    private int $order;

    private function __construct(TaxonomyId $taxonomyId, TaxonomyType $type, TaxonomyState $state)
    {
        $this->taxonomyId = $taxonomyId;
        $this->type = $type;
        $this->state = $state;
    }

    public static function create(TaxonomyId $taxonomyId, TaxonomyType $type): static
    {
        $object = new self($taxonomyId, $type, TaxonomyState::online);

        $object->showsAsGridFilter = false;
        $object->showsInGrid = false;
        $object->allowsMultipleValues = false;
        $object->allowsNestableValues = in_array($type, [
            TaxonomyType::category,
        ]);

        $object->order = 0;

        $object->recordEvent(new TaxonomyCreated($object->taxonomyId));

        return $object;
    }

    public function getType(): TaxonomyType
    {
        return $this->type;
    }

    public function getState(): TaxonomyState
    {
        return $this->state;
    }

    public function getOrder(): int
    {
        return $this->order;
    }

    public function showsAsGridFilter(): bool
    {
        return $this->showsAsGridFilter;
    }

    public function showsInGrid(): bool
    {
        return $this->showsInGrid;
    }

    public function allowsMultipleValues(): bool
    {
        return $this->allowsMultipleValues;
    }

    public function allowsNestableValues(): bool
    {
        return $this->allowsNestableValues;
    }

    public function changeType(TaxonomyType $type): void
    {
        $this->type = $type;
    }

    public function changeState(TaxonomyState $state): void
    {
        $this->state = $state;
    }

    public function changeOrder(int $order): void
    {
        $this->order = $order;
    }

    public function showAsGridFilter(bool $showsAsGridFilter = true): void
    {
        $this->showsAsGridFilter = $showsAsGridFilter;
    }

    public function showInGrid(bool $showsInGrid = true): void
    {
        $this->showsInGrid = $showsInGrid;
    }

    public function allowMultipleValues(bool $allowsMultipleValues = true): void
    {
        $this->allowsMultipleValues = $allowsMultipleValues;
    }

    public function allowNestableValues(bool $allowsNestableValues = true): void
    {
        $this->allowsNestableValues = $allowsNestableValues;
    }

    public function getMappedData(): array
    {
        return [
            'taxonomy_id' => $this->taxonomyId->get(),
            'type' => $this->type?->value ?? null,
            'state' => $this->state->value,
            'shows_as_grid_filter' => $this->showsAsGridFilter,
            'shows_in_grid' => $this->showsInGrid,
            'allows_multiple_values' => $this->allowsMultipleValues,
            'allows_nestable_values' => $this->allowsNestableValues,
            'order' => $this->order,
            'data' => json_encode($this->data),
        ];
    }

    public function getChildEntities(): array
    {
        return [

        ];
    }

    public static function fromMappedData(array $state, array $childEntities = []): static
    {
        $object = new self(
            TaxonomyId::fromString($state['taxonomy_id']),
            TaxonomyType::from($state['type']),
            TaxonomyState::from($state['state'])
        );

        $object->showsAsGridFilter = (bool)$state['shows_as_grid_filter'];
        $object->showsInGrid = (bool)$state['shows_in_grid'];
        $object->allowsMultipleValues = (bool)$state['allows_multiple_values'];
        $object->allowsNestableValues = (bool)$state['allows_nestable_values'];
        $object->order = (int)$state['order'];
        $object->data = $state['data'] ? json_decode($state['data'], true) : [];

        return $object;
    }
}
