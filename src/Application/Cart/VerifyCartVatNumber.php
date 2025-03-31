<?php
declare(strict_types=1);

namespace Thinktomorrow\Trader\Application\Cart;

use Thinktomorrow\Trader\Domain\Model\Order\OrderId;

class VerifyCartVatNumber
{
    private string $orderId;
    private ?string $vatNumber;

    public function __construct(string $orderId, string $vatNumber)
    {
        $this->orderId = $orderId;
        $this->vatNumber = $vatNumber;
    }

    public function getOrderId(): OrderId
    {
        return OrderId::fromString($this->orderId);
    }

    public function getVatNumber(): string
    {
        return $this->vatNumber;
    }
}
