<?php
declare(strict_types=1);

namespace Thinktomorrow\Trader\Infrastructure\Laravel;

use Illuminate\Database\Connection;
use Thinktomorrow\Trader\Domain\Model\Order\Order;
use Thinktomorrow\Trader\Domain\Model\Order\OrderId;
use Thinktomorrow\Trader\Domain\Model\Order\OrderRepository;

final class MysqlOrderRepository implements OrderRepository
{
    private Connection $connection;

    public function __construct(Connection $connection)
    {
        $this->connection = $connection;
    }

    public function save(Order $order): void
    {
        $exists = $this->connection->select('SELECT count(order_id) FROM orders WHERE order_id = ?', $order->orderId->get());

        if($exists) {
            // Update order, lines, shipping, discounts, ....
//            $this->connection->update();
        }
        // TODO: Implement save() method.
    }

    public function find(OrderId $orderId): Order
    {
        // TODO: Implement find() method.
    }

    public function delete(OrderId $orderId): void
    {
        // TODO: Implement delete() method.
    }

    public function nextReference(): OrderId
    {
        // TODO: Implement nextReference() method.
    }
}
