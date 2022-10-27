<?php
declare(strict_types=1);

namespace Thinktomorrow\Trader\Domain\Model\Taxon;

use Thinktomorrow\Trader\Domain\Common\Entity\Aggregate;
use Thinktomorrow\Trader\Domain\Common\Entity\HasData;
use Thinktomorrow\Trader\Domain\Common\Event\RecordsEvents;
use Thinktomorrow\Trader\Domain\Model\Taxon\Events\TaxonCreated;
use Thinktomorrow\Trader\Domain\Model\Taxon\Exceptions\InvalidParentTaxonId;

class Taxon implements Aggregate
{
    use HasTaxonKeys;
    use HasData;
    use RecordsEvents;

    public readonly TaxonId $taxonId;
    private TaxonState $taxonState;
    private int $order;
    private ?TaxonId $parentTaxonId = null;

    public static function create(TaxonId $taxonId, ?TaxonId $parentTaxonId = null): static
    {
        $taxon = new static();
        $taxon->taxonId = $taxonId;
        $taxon->taxonState = TaxonState::online;
        $taxon->order = 0;

        $parentTaxonId
            ? $taxon->changeParent($parentTaxonId)
            : $taxon->moveToRoot();

        $taxon->recordEvent(new TaxonCreated($taxon->taxonId));

        return $taxon;
    }

    public function getParentId(): ?TaxonId
    {
        return $this->parentTaxonId;
    }

    public function changeParent(TaxonId $parentTaxonId): void
    {
        if ($this->taxonId->equals($parentTaxonId)) {
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
            'state' => $this->taxonState->value,
            'order' => $this->order,
            'parent_id' => $this->parentTaxonId?->get(),
            'data' => json_encode($this->data),
        ];
    }

    public function getChildEntities(): array
    {
        return [
            TaxonKey::class => array_map(fn (TaxonKey $taxonKey) => $taxonKey->getMappedData(), $this->taxonKeys),
        ];
    }

    public static function fromMappedData(array $state, array $childEntities = []): static
    {
        $taxon = new static();
        $taxon->taxonId = TaxonId::fromString($state['taxon_id']);
        $taxon->taxonState = TaxonState::from($state['state']);
        $taxon->order = (int) $state['order'];
        $taxon->data = json_decode($state['data'], true);
        $taxon->parentTaxonId = $state['parent_id'] ? TaxonId::fromString($state['parent_id']) : null;

        $taxon->taxonKeys = array_map(fn ($taxonKeyState) => TaxonKey::fromMappedData($taxonKeyState, $state), $childEntities[TaxonKey::class]);

        return $taxon;
    }
}
