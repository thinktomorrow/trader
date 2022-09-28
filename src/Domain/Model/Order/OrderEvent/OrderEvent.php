<?php
declare(strict_types=1);

namespace Thinktomorrow\Trader\Domain\Model\Order\OrderEvent;

use Thinktomorrow\Trader\Domain\Common\Entity\ChildEntity;
use Thinktomorrow\Trader\Domain\Common\Entity\HasData;

final class OrderEvent implements ChildEntity
{
    use HasData;

    public readonly OrderEventId $orderEventId;
    private string $event;
    private \DateTime $createdAt;

    public static function create(OrderEventId $orderEventId, string $event, \DateTime $createdAt, array $data)
    {
        $entry = new static();
        $entry->orderEventId = $orderEventId;
        $entry->event = $event;
        $entry->createdAt = $createdAt;
        $entry->data = $data;

        return $entry;
    }

    public function getEvent(): string
    {
        return $this->event;
    }

    public function getCreatedAt(): \DateTime
    {
        return $this->createdAt;
    }

    public function getMappedData(): array
    {
        return [
            'entry_id' => $this->orderEventId->get(),
            'event' => $this->event,
            'at' => $this->createdAt->format('Y-m-d H:i:s'),
            'data' => json_encode($this->data),
        ];
    }

    public static function fromMappedData(array $state, array $aggregateState): static
    {
        $entry = new static();

        $entry->orderEventId = OrderEventId::fromString($state['entry_id']);
        $entry->event = $state['event'];
        $entry->createdAt = new \DateTime($state['at']);
        $entry->data = json_decode($state['data'], true);

        return $entry;
    }
}
