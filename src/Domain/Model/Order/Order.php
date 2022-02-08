<?php
declare(strict_types=1);

namespace Thinktomorrow\Trader\Domain\Model\Order;

use Thinktomorrow\Trader\Domain\Model\Product\ProductId;
use Thinktomorrow\Trader\Domain\Common\Entity\Aggregate;
use Thinktomorrow\Trader\Domain\Model\Customer\CustomerId;
use Thinktomorrow\Trader\Domain\Common\Event\RecordsEvents;
use Thinktomorrow\Trader\Domain\Model\Order\Events\LineAdded;
use Thinktomorrow\Trader\Domain\Model\Order\Events\LineUpdated;
use Thinktomorrow\Trader\Domain\Model\Order\Events\LineDeleted;
use Thinktomorrow\Trader\Domain\Model\Order\Events\OrderUpdated;
use Thinktomorrow\Trader\Domain\Model\Order\Events\OrderCreated;

final class Order implements Aggregate
{
    use RecordsEvents;

    public readonly OrderId $orderId;

    private CustomerId $customerId;

    private array $lines = [];

    private function __construct(){}

    public static function create(OrderId $orderId, CustomerId $customerId)
    {
        $order = new static();

        $order->orderId = $orderId;
        $order->customerId = $customerId;

        $order->recordEvent(new OrderCreated($order->orderId));

        return $order;
    }

    // addresses, methods, state,
    public function update(CustomerId $customerId): void
    {
        $this->customerId = $customerId;

        $this->recordEvent(new OrderUpdated($this->orderId));
    }

    public function addOrUpdateLine(LineNumber $lineNumber, ProductId $productId, Quantity $quantity): void
    {
        if(null !== $this->findLineIndex($lineNumber)) {
            $this->updateLine($lineNumber, $productId, $quantity);
            return;
        }

        $this->addLine($lineNumber, $productId, $quantity);
    }

    private function addLine(LineNumber $lineNumber, ProductId $productId, Quantity $quantity): void
    {
        $this->lines[] = Line::create($this->orderId, $lineNumber, $productId, $quantity);

        $this->recordEvent(new LineAdded($this->orderId, $lineNumber, $productId, $quantity));
    }

    private function updateLine(LineNumber $lineNumber, ProductId $productId, Quantity $quantity): void
    {
        if(null !== $lineIndexToBeUpdated = $this->findLineIndex($lineNumber)) {
            $this->lines[$lineIndexToBeUpdated]->update($productId, $quantity);
        }

        $this->recordEvent(new LineUpdated($this->orderId, $lineNumber, $productId, $quantity));
    }

    public function deleteLine(LineNumber $lineNumber): void
    {
        if(null !== $lineIndexToBeDeleted = $this->findLineIndex($lineNumber)) {
            $lineToBeDeleted = $this->lines[$lineIndexToBeDeleted];

            unset($this->lines[$lineIndexToBeDeleted]);

            $this->recordEvent(new LineDeleted($this->orderId, $lineToBeDeleted->lineNumber, $lineToBeDeleted->getProductId()));
        }
    }

    // Update state
    // getState
    // ...

    // Getters...

    // -> custom setters and getters for a project. e.g. add extra notes field to an order...

    public function getMappedData(): array
    {
        return [
            'order_id' => $this->orderId->get(),
            'customer_id' => $this->customerId->get(),
        ];
    }

    public function getChildEntities(): array
    {
        return [
            Line::class => $this->lines,
        ];
    }

    public static function fromMappedData(array $state, array $childEntities = []): static
    {
        $order = new static();

        $order->orderId = OrderId::fromString($state['order_id']);
        $order->customerId = CustomerId::fromString($state['customer_id']);

        // columns => values
        // What about relations

        return $order;
    }

    private function findLineIndex(LineNumber $lineNumber): ?int
    {
        foreach($this->lines as $index => $line) {
            if($lineNumber->asInt() === $line->lineNumber->asInt()) {
                return $index;
            }
        }

        return null;
    }

    // REPO
    // save
        // insertOrUpdate
        // Delete any child entities
        // insertOrUpdate child entities
        // Saved event...



//    v     private OrderReference $reference;
//    v     private OrderCustomer $orderCustomer;
//    private string $orderState;
//    private OrderProductCollection $items;
//    private OrderShipping $orderShipping;
//    private OrderPayment $orderPayment;
//    private AppliedDiscountCollection $discounts;
//    private NoteCollection $notes;
//    private array $data;
}
