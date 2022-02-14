<?php
declare(strict_types=1);

namespace Thinktomorrow\Trader\Application\Cart;

use Thinktomorrow\Trader\Domain\Common\Context;
use Thinktomorrow\Trader\Domain\Model\Order\OrderId;

final class RefreshCart
{
    private string $orderId;
    private array $adjusters;
    private Context $context;

    public function __construct(string $orderId, array $adjusters, Context $context)
    {
        $this->orderId = $orderId;
        $this->adjusters = $adjusters;
        $this->context = $context;
    }

    public function getOrderId(): OrderId
    {
        return OrderId::fromString($this->orderId);
    }

    public function getAdjusters(): array
    {
        return $this->adjusters;
    }

    public function getContext(): Context
    {
        return $this->context;
    }
}
