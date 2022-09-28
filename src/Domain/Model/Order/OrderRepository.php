<?php
declare(strict_types=1);

namespace Thinktomorrow\Trader\Domain\Model\Order;

use Thinktomorrow\Trader\Domain\Model\Order\Discount\DiscountId;
use Thinktomorrow\Trader\Domain\Model\Order\Exceptions\OrderAlreadyInMerchantHands;
use Thinktomorrow\Trader\Domain\Model\Order\Line\LineId;
use Thinktomorrow\Trader\Domain\Model\Order\Line\Personalisations\LinePersonalisationId;
use Thinktomorrow\Trader\Domain\Model\Order\OrderEvent\OrderEventId;
use Thinktomorrow\Trader\Domain\Model\Order\Payment\PaymentId;
use Thinktomorrow\Trader\Domain\Model\Order\Shipping\ShippingId;

interface OrderRepository
{
    public function save(Order $order): void;

    public function find(OrderId $orderId): Order;

    /**
     * Same as find() but with assertion that the order is still in customer hands and valid for cart manipulation.
     * When the order is already in merchant hands, this method will throw a halting exception.
     *
     * @throws OrderAlreadyInMerchantHands
     */
    public function findForCart(OrderId $orderId): Order;

    public function delete(OrderId $orderId): void;

    public function findIdByReference(OrderReference $orderReference): OrderId;

    public function nextReference(): OrderId;

    public function nextExternalReference(): OrderReference;

    public function nextShippingReference(): ShippingId;

    public function nextPaymentReference(): PaymentId;

    public function nextShopperReference(): ShopperId;

    public function nextDiscountReference(): DiscountId;

    public function nextLineReference(): LineId;

    public function nextLinePersonalisationReference(): LinePersonalisationId;

    public function nextLogEntryReference(): OrderEventId;
}
