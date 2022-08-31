<?php
declare(strict_types=1);

namespace Thinktomorrow\Trader\Application\Order\Merchant;

use Thinktomorrow\Trader\Domain\Model\Order\OrderId;

class AddLogEntry
{
    private string $orderId;
    private string $event;
    private array $data;

    public function __construct(string $orderId, string $event, array $data)
    {
        $this->orderId = $orderId;
        $this->event = $event;
        $this->data = $data;
    }

    public function getOrderId(): OrderId
    {
        return OrderId::fromString($this->orderId);
    }

    public function getEvent(): string
    {
        return $this->event;
    }

    public function getData(): array
    {
        return $this->data;
    }
}
