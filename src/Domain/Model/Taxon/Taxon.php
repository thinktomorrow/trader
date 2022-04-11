<?php
declare(strict_types=1);

namespace Thinktomorrow\Trader\Domain\Model\Taxon;

use Thinktomorrow\Trader\Domain\Common\Entity\Aggregate;
use Thinktomorrow\Trader\Domain\Common\Event\RecordsEvents;
use Thinktomorrow\Trader\Domain\Model\Taxon\Exceptions\InvalidParentTaxonId;

class Taxon implements Aggregate
{
    use RecordsEvents;

    public readonly TaxonId $taxonId;
    private string $taxonKey;
    private TaxonState $taxonState;
    private int $order;
    private ?TaxonId $parentTaxonId = null;

    public static function create(TaxonId $taxonId, string $taxonKey, ?TaxonId $parentTaxonId = null): static
    {
        $taxon = new static();
        $taxon->taxonId = $taxonId;
        $taxon->taxonKey = $taxonKey;
        $taxon->taxonState = TaxonState::online;
        $taxon->order = 0;

        $parentTaxonId
            ? $taxon->changeParent($parentTaxonId)
            : $taxon->moveToRoot();

        return $taxon;
    }

    public function changeParent(TaxonId $parentTaxonId): void
    {
        if($this->taxonId->equals($parentTaxonId)) {
            throw new InvalidParentTaxonId('Parent taxon id should be different than child taxon id.');
        }

        $this->parentTaxonId = $parentTaxonId;
    }

    public function moveToRoot(): void
    {
        $this->parentTaxonId = null;
    }

    public function changeState(TaxonState $state): void
    {
        $this->taxonState = $state;
    }

    public function changeOrder(int $order): void
    {
        $this->order = $order;
    }

    public function getMappedData(): array
    {
        return [
            'taxon_id' => $this->taxonId->get(),
            'key' => $this->taxonKey,
            'state' => $this->taxonState->value,
            'order' => $this->order,
            'parent_id' => $this->parentTaxonId?->get(),
        ];
    }

    public function getChildEntities(): array
    {
        return [];
    }

    public static function fromMappedData(array $state, array $childEntities = []): static
    {
        $taxon = new static();
        $taxon->taxonId = TaxonId::fromString($state['taxon_id']);
        $taxon->taxonKey = $state['key'];
        $taxon->taxonState = TaxonState::from($state['state']);
        $taxon->order = (int) $state['order'];
        $taxon->parentTaxonId = $state['parent_id'] ? TaxonId::fromString($state['parent_id']) : null;

        return $taxon;
    }
}
