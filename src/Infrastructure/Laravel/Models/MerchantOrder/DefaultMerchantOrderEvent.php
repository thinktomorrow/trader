<?php
declare(strict_types=1);

namespace Thinktomorrow\Trader\Infrastructure\Laravel\Models\MerchantOrder;

use Thinktomorrow\Trader\Application\Common\RendersData;
use Thinktomorrow\Trader\Application\Order\MerchantOrder\MerchantOrderEvent;

class DefaultMerchantOrderEvent implements MerchantOrderEvent
{
    use RendersData;

    protected string $entry_id;
    protected string $event;
    protected \DateTime $createdAt;
    protected array $data;

    final public function __construct()
    {
    }

    public static function fromMappedData(array $state, array $orderState): static
    {
        $entry = new static();

        $entry->entry_id = $state['entry_id'];
        $entry->event = $state['event'];
        $entry->createdAt = new \DateTime($state['at']);
        $entry->data = json_decode($state['data'], true);

        return $entry;
    }

    public function getOrderEventId(): string
    {
        return $this->entry_id;
    }

    public function getEvent(): string
    {
        return $this->event;
    }

    public function getCreatedAt(): \DateTime
    {
        return $this->createdAt;
    }

    public function getData(string $key, ?string $language = null, $default = null)
    {
        return $this->data($key, $language, $default);
    }
}
