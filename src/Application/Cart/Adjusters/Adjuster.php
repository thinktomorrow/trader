<?php
declare(strict_types=1);

namespace Thinktomorrow\Trader\Application\Cart\Adjusters;

use Thinktomorrow\Trader\Domain\Common\Context;
use Thinktomorrow\Trader\Domain\Model\Order\Entity\Order;
use Thinktomorrow\Trader\Domain\Model\Order\Details\OrderDetails;

interface Adjuster
{
    public function adjust(Order $order, OrderDetails $orderDetails, Context $context): void;
}
