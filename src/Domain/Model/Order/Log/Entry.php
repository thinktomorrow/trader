<?php
declare(strict_types=1);

namespace Thinktomorrow\Trader\Domain\Model\Order\Log;

use Thinktomorrow\Trader\Domain\Common\Entity\HasData;
use Thinktomorrow\Trader\Domain\Common\Entity\ChildEntity;

final class Entry implements ChildEntity
{
    use HasData;

    private string $key;
    private \DateTime $createdAt;

    public static function create(string $key, array $data)
    {
        $event = new static();
        $event->key = $key;
        $event->createdAt = new \DateTime();
        $event->data = $data;

        return $event;
    }

    public function getMappedData(): array
    {
        return [
            'key' => $this->key,
            'created_at' => $this->createdAt->format('Y-m-d H:i:s'),
            'data' => json_encode($this->data),
        ];
    }

    public static function fromMappedData(array $state, array $aggregateState): static
    {
        $event = new static();

        $event->key = $state['key'];
        $event->createdAt = new \DateTime($state['created_at']);
        $event->data = json_decode($state['data'], true);

        return $event;
    }
}
