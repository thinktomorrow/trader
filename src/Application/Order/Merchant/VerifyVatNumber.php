<?php
declare(strict_types=1);

namespace Thinktomorrow\Trader\Application\Order\Merchant;

use Thinktomorrow\Trader\Domain\Model\Order\OrderId;

class VerifyVatNumber
{
    private string $orderId;
    private string $vatNumber;

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
