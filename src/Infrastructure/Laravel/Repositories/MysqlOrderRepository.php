<?php
declare(strict_types=1);

namespace Thinktomorrow\Trader\Infrastructure\Laravel\Repositories;

use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Psr\Container\ContainerInterface;
use Ramsey\Uuid\Uuid;
use Thinktomorrow\Trader\Application\Order\Invoice\CreateInvoiceReferenceByYearAndMonth;
use Thinktomorrow\Trader\Domain\Common\Address\AddressType;
use Thinktomorrow\Trader\Domain\Model\Order\Address\BillingAddress;
use Thinktomorrow\Trader\Domain\Model\Order\Address\ShippingAddress;
use Thinktomorrow\Trader\Domain\Model\Order\Discount\Discount;
use Thinktomorrow\Trader\Domain\Model\Order\Discount\DiscountableType;
use Thinktomorrow\Trader\Domain\Model\Order\Discount\DiscountId;
use Thinktomorrow\Trader\Domain\Model\Order\Exceptions\CouldNotFindOrder;
use Thinktomorrow\Trader\Domain\Model\Order\Exceptions\OrderAlreadyInMerchantHands;
use Thinktomorrow\Trader\Domain\Model\Order\Invoice\InvoiceReference;
use Thinktomorrow\Trader\Domain\Model\Order\Invoice\InvoiceRepository;
use Thinktomorrow\Trader\Domain\Model\Order\Line\Line;
use Thinktomorrow\Trader\Domain\Model\Order\Line\LineId;
use Thinktomorrow\Trader\Domain\Model\Order\Line\Personalisations\LinePersonalisation;
use Thinktomorrow\Trader\Domain\Model\Order\Line\Personalisations\LinePersonalisationId;
use Thinktomorrow\Trader\Domain\Model\Order\Order;
use Thinktomorrow\Trader\Domain\Model\Order\OrderEvent\OrderEvent;
use Thinktomorrow\Trader\Domain\Model\Order\OrderEvent\OrderEventId;
use Thinktomorrow\Trader\Domain\Model\Order\OrderId;
use Thinktomorrow\Trader\Domain\Model\Order\OrderReference;
use Thinktomorrow\Trader\Domain\Model\Order\OrderRepository;
use Thinktomorrow\Trader\Domain\Model\Order\Payment\Payment;
use Thinktomorrow\Trader\Domain\Model\Order\Payment\PaymentId;
use Thinktomorrow\Trader\Domain\Model\Order\Payment\PaymentState;
use Thinktomorrow\Trader\Domain\Model\Order\Shipping\Shipping;
use Thinktomorrow\Trader\Domain\Model\Order\Shipping\ShippingId;
use Thinktomorrow\Trader\Domain\Model\Order\Shipping\ShippingState;
use Thinktomorrow\Trader\Domain\Model\Order\Shopper;
use Thinktomorrow\Trader\Domain\Model\Order\ShopperId;
use Thinktomorrow\Trader\Domain\Model\Order\State\OrderState;
use Thinktomorrow\Trader\TraderConfig;

class MysqlOrderRepository implements OrderRepository, InvoiceRepository
{
    private static $orderTable = 'trader_orders';
    private static $orderLinesTable = 'trader_order_lines';
    private static $orderLinePersonalisationsTable = 'trader_order_line_personalisations';
    private static $orderDiscountsTable = 'trader_order_discounts';
    private static $orderShippingTable = 'trader_order_shipping';
    private static $orderPaymentTable = 'trader_order_payment';
    private static $orderAddressTable = 'trader_order_addresses';
    private static $orderShopperTable = 'trader_order_shoppers';
    private static $orderEventsTable = 'trader_order_events';

    private ContainerInterface $container;
    protected TraderConfig $traderConfig;

    public function __construct(ContainerInterface $container, TraderConfig $traderConfig)
    {
        $this->container = $container;
        $this->traderConfig = $traderConfig;
    }

    public function save(Order $order): void
    {
        $state = $order->getMappedData();

        if (! $this->exists($order->orderId)) {
            DB::table(static::$orderTable)->insert(array_merge($state, [
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
            ]));
        } else {
            DB::table(static::$orderTable)->where('order_id', $order->orderId->get())->update(array_merge($state, [
                'updated_at' => Carbon::now(),
            ]));
        }

        $this->upsertLines($order);
        $this->upsertLinePersonalisations($order);
        $this->upsertDiscounts($order);
        $this->upsertShippings($order);
        $this->upsertPayments($order);
        $this->upsertAddresses($order);
        $this->upsertShopper($order);
        $this->upsertEvents($order);
    }

    private function upsertLines(Order $order): void
    {
        $lineIds = array_map(fn ($lineState) => $lineState['line_id'], $order->getChildEntities()[Line::class]);

        DB::table(static::$orderLinesTable)
            ->where('order_id', $order->orderId->get())
            ->whereNotIn('line_id', $lineIds)
            ->delete();

        // TODO:delete line discounts...

        foreach ($order->getChildEntities()[Line::class] as $lineState) {
            DB::table(static::$orderLinesTable)
                ->updateOrInsert([
                    'order_id' => $order->orderId->get(),
                    'line_id' => $lineState['line_id'],
                ], $lineState);

            // TODO: how to save line discounts...
        }
    }

    private function upsertDiscounts(Order $order): void
    {
        $discountStates = $order->getChildEntities()[Discount::class];

        foreach ($order->getLines() as $line) {
            $discountStates = array_merge($discountStates, $line->getChildEntities()[Discount::class]);
        }

        foreach ($order->getShippings() as $shipping) {
            $discountStates = array_merge($discountStates, $shipping->getChildEntities()[Discount::class]);
        }

        foreach ($order->getPayments() as $payment) {
            $discountStates = array_merge($discountStates, $payment->getChildEntities()[Discount::class]);
        }

        DB::table(static::$orderDiscountsTable)
            ->where('order_id', $order->orderId->get())
            ->whereNotIn('discount_id', array_map(fn ($discountState) => $discountState['discount_id'], $discountStates))
            ->delete();

        foreach ($discountStates as $discountState) {
            DB::table(static::$orderDiscountsTable)
                ->updateOrInsert([
                    'order_id' => $order->orderId->get(),
                    'discount_id' => $discountState['discount_id'],
                    'discountable_type' => $discountState['discountable_type'],
                    'discountable_id' => $discountState['discountable_id'],
                ], $discountState);
        }
    }

    private function upsertLinePersonalisations(Order $order): void
    {
        $personalisationStates = [];

        foreach ($order->getLines() as $line) {
            $personalisationStates = array_merge($personalisationStates, $line->getChildEntities()[LinePersonalisation::class]);
        }

        DB::table(static::$orderLinePersonalisationsTable)
            ->where('order_id', $order->orderId->get())
            ->whereNotIn('line_personalisation_id', array_map(fn ($personalisationsState) => $personalisationsState['line_personalisation_id'], $personalisationStates))
            ->delete();

        foreach ($personalisationStates as $personalisationsState) {
            DB::table(static::$orderLinePersonalisationsTable)
                ->updateOrInsert([
                    'order_id' => $order->orderId->get(),
                    'line_personalisation_id' => $personalisationsState['line_personalisation_id'],
                    'personalisation_type' => $personalisationsState['personalisation_type'],
                    'personalisation_id' => $personalisationsState['personalisation_id'],
                ], $personalisationsState);
        }
    }

    private function upsertShippings(Order $order): void
    {
        $shippingIds = array_map(fn ($shippingState) => $shippingState['shipping_id'], $order->getChildEntities()[Shipping::class]);

        DB::table(static::$orderShippingTable)
            ->where('order_id', $order->orderId->get())
            ->whereNotIn('shipping_id', $shippingIds)
            ->delete();

        foreach ($order->getChildEntities()[Shipping::class] as $shippingState) {
            DB::table(static::$orderShippingTable)
                ->updateOrInsert([
                    'order_id' => $order->orderId->get(),
                    'shipping_id' => $shippingState['shipping_id'],
                ], $shippingState);

            // TODO: how to save shipping discounts...
        }
    }

    private function upsertPayments(Order $order): void
    {
        $paymentIds = array_map(fn ($paymentState) => $paymentState['payment_id'], $order->getChildEntities()[Payment::class]);

        DB::table(static::$orderPaymentTable)
            ->where('order_id', $order->orderId->get())
            ->whereNotIn('payment_id', $paymentIds)
            ->delete();

        foreach ($order->getChildEntities()[Payment::class] as $paymentState) {
            DB::table(static::$orderPaymentTable)
                ->updateOrInsert([
                    'order_id' => $order->orderId->get(),
                    'payment_id' => $paymentState['payment_id'],
                ], $paymentState);
        }
    }

    private function upsertAddresses(Order $order): void
    {
        if ($shippingAddressState = $order->getChildEntities()[ShippingAddress::class]) {
            DB::table(static::$orderAddressTable)
                ->updateOrInsert([
                    'order_id' => $order->orderId->get(),
                    'type' => AddressType::shipping->value,
                ], $shippingAddressState);
        } else {
            DB::table(static::$orderAddressTable)
                ->where('order_id', $order->orderId->get())
                ->where('type', AddressType::shipping->value)
                ->delete();
        }

        if ($billingAddressState = $order->getChildEntities()[BillingAddress::class]) {
            DB::table(static::$orderAddressTable)
                ->updateOrInsert([
                    'order_id' => $order->orderId->get(),
                    'type' => AddressType::billing->value,
                ], $billingAddressState);
        } else {
            DB::table(static::$orderAddressTable)
                ->where('order_id', $order->orderId->get())
                ->where('type', AddressType::billing->value)
                ->delete();
        }
    }

    private function upsertEvents(Order $order): void
    {
        $orderEventIds = array_map(fn ($orderEvent) => $orderEvent->orderEventId->get(), $order->getOrderEvents());

        DB::table(static::$orderEventsTable)
            ->where('order_id', $order->orderId->get())
            ->whereNotIn('entry_id', $orderEventIds)
            ->delete();

        foreach ($order->getChildEntities()[OrderEvent::class] as $orderEventState) {
            DB::table(static::$orderEventsTable)
                ->updateOrInsert([
                    'order_id' => $order->orderId->get(),
                    'entry_id' => $orderEventState['entry_id'],
                ], $orderEventState);
        }
    }

    private function upsertShopper(Order $order): void
    {
        $shopperState = $order->getChildEntities()[Shopper::class];

        if (is_null($shopperState)) {
            DB::table(static::$orderShopperTable)->where('order_id', $order->orderId->get())->delete();

            return;
        }

        $shopperState = $this->prepareShopperStateForStorage($shopperState);

        DB::table(static::$orderShopperTable)
            ->updateOrInsert([
                'order_id' => $order->orderId->get(),
                'shopper_id' => $shopperState['shopper_id'],
            ], $shopperState);
    }

    protected function prepareShopperStateForStorage(array $shopperState): array
    {
        return $shopperState;
    }

    protected function prepareShopperStateForModel(array $shopperState): array
    {
        return $shopperState;
    }

    private function exists(OrderId $orderId): bool
    {
        return DB::table(static::$orderTable)->where('order_id', $orderId->get())->exists();
    }

    private function existsReference(OrderReference $orderReference): bool
    {
        return DB::table(static::$orderTable)->where('order_ref', $orderReference->get())->exists();
    }

    protected function existsInvoiceReference(InvoiceReference $invoiceReference): bool
    {
        return DB::table(static::$orderTable)->where('invoice_ref', $invoiceReference->get())->exists();
    }

    public function find(OrderId $orderId): Order
    {
        $orderState = DB::table(static::$orderTable)
            ->where(static::$orderTable . '.order_id', $orderId->get())
            ->first();

        if (! $orderState) {
            throw new CouldNotFindOrder('No order found by id [' . $orderId->get() . ']');
        }

        $allDiscountStates = DB::table(static::$orderDiscountsTable)
            ->where(static::$orderDiscountsTable . '.order_id', $orderId->get())
            ->get()
            ->map(fn ($item) => (array)$item)
            ->map(fn ($item) => array_merge($item, ['includes_vat' => (bool)$item['includes_vat']]));

        $allPersonalisationStates = DB::table(static::$orderLinePersonalisationsTable)
            ->where(static::$orderLinePersonalisationsTable . '.order_id', $orderId->get())
            ->get()
            ->map(fn ($item) => (array)$item);

        $lineStates = DB::table(static::$orderLinesTable)
            ->where(static::$orderLinesTable . '.order_id', $orderId->get())
            ->get()
            ->map(fn ($item) => (array)$item)
            ->map(fn ($item) => array_merge($item, [
                'includes_vat' => (bool)$item['includes_vat'],
                'reduced_from_stock' => (bool)$item['reduced_from_stock'],
                Discount::class => $allDiscountStates->filter(fn ($discountState) => $discountState['discountable_type'] == DiscountableType::line->value && $discountState['discountable_id'] == $item['line_id'])->values()->toArray(),
                LinePersonalisation::class => $allPersonalisationStates->filter(fn ($personalisationState) => $personalisationState['line_id'] == $item['line_id'])->values()->toArray(),
            ]))
            ->toArray();

        $shippingStates = DB::table(static::$orderShippingTable)
            ->where(static::$orderShippingTable . '.order_id', $orderId->get())
            ->get()
            ->map(fn ($item) => (array)$item)
            ->map(fn ($item) => array_merge($item, [
                'shipping_state' => $this->container->get(ShippingState::class)::fromString($item['shipping_state']),
                'includes_vat' => (bool)$item['includes_vat'],
                Discount::class => $allDiscountStates->filter(fn ($discountState) => $discountState['discountable_type'] == DiscountableType::shipping->value && $discountState['discountable_id'] == $item['shipping_id'])->values()->toArray(),
            ]))
            ->toArray();

        $paymentStates = DB::table(static::$orderPaymentTable)
            ->where(static::$orderPaymentTable . '.order_id', $orderId->get())
            ->get()
            ->map(fn ($item) => (array)$item)
            ->map(fn ($item) => array_merge($item, [
                'payment_state' => $this->container->get(PaymentState::class)::fromString($item['payment_state']),
                'includes_vat' => (bool)$item['includes_vat'],
                Discount::class => $allDiscountStates->filter(fn ($discountState) => $discountState['discountable_type'] == DiscountableType::payment->value && $discountState['discountable_id'] == $item['payment_id'])->values()->toArray(),
            ]))
            ->toArray();

        $addressStates = DB::table(static::$orderAddressTable)
            ->where(static::$orderAddressTable . '.order_id', $orderId->get())
            ->get();

        $shippingAddressState = $addressStates->first(fn ($address) => $address->type == AddressType::shipping->value);
        $billingAddressState = $addressStates->first(fn ($address) => $address->type == AddressType::billing->value);

        $shopperState = DB::table(static::$orderShopperTable)
            ->where(static::$orderShopperTable . '.order_id', $orderId->get())
            ->first();

        if (! is_null($shopperState)) {
            $shopperState = $this->prepareShopperStateForModel((array)$shopperState);

            $shopperState = array_merge($shopperState, [
                'register_after_checkout' => (bool)$shopperState['register_after_checkout'],
                'is_business' => (bool)$shopperState['is_business'],
            ]);
        }

        $orderEventStates = DB::table(static::$orderEventsTable)
            ->where(static::$orderEventsTable . '.order_id', $orderId->get())
            ->orderBy('at', 'ASC')
            ->get()
            ->map(fn ($item) => (array)$item)
            ->toArray();

        $childEntities = [
            Line::class => $lineStates,
            Discount::class => $allDiscountStates->filter(fn ($discountState) => $discountState['discountable_type'] == DiscountableType::order->value && $discountState['discountable_id'] == $orderState->order_id)->values()->toArray(),
            Shipping::class => $shippingStates,
            Payment::class => $paymentStates,
            Shopper::class => $shopperState,
            ShippingAddress::class => $shippingAddressState ? (array)$shippingAddressState : null,
            BillingAddress::class => $billingAddressState ? (array)$billingAddressState : null,
            OrderEvent::class => $orderEventStates,
        ];

        $orderState = (array) $orderState;

        return Order::fromMappedData(array_merge($orderState, [
            'order_state' => $this->container->get(OrderState::class)::fromString($orderState['order_state']),
        ]), $childEntities);
    }

    public function findForCart(OrderId $orderId): Order
    {
        $order = $this->find($orderId);

        if (! $order->inCustomerHands()) {
            throw new OrderAlreadyInMerchantHands('Cannot fetch order for cart. Order is no longer in customer hands and has already the following state: ' . $order->getOrderState()->value);
        }

        return $order;
    }

    public function delete(OrderId $orderId): void
    {
        DB::table(static::$orderShopperTable)->where('order_id', $orderId->get())->delete();
        DB::table(static::$orderShippingTable)->where('order_id', $orderId->get())->delete();
        DB::table(static::$orderPaymentTable)->where('order_id', $orderId->get())->delete();
        DB::table(static::$orderLinesTable)->where('order_id', $orderId->get())->delete();
        DB::table(static::$orderLinePersonalisationsTable)->where('order_id', $orderId->get())->delete();
        DB::table(static::$orderAddressTable)->where('order_id', $orderId->get())->delete();
        DB::table(static::$orderDiscountsTable)->where('order_id', $orderId->get())->delete();
        DB::table(static::$orderTable)->where('order_id', $orderId->get())->delete();
    }

    public function findIdByReference(OrderReference $orderReference): OrderId
    {
        $order_id = DB::table(static::$orderTable)->select('order_id')->where('order_ref', $orderReference->get())->first()?->order_id;

        if (! $order_id) {
            throw new CouldNotFindOrder('No order found by order reference ' . $orderReference->get());
        }

        return OrderId::fromString($order_id);
    }

    public function nextReference(): OrderId
    {
        return OrderId::fromString((string)Uuid::uuid4());
    }

    public function nextExternalReference(): OrderReference
    {
        $orderReference = null;

        while (! $orderReference || $this->existsReference($orderReference)) {
            $orderReference = OrderReference::fromString($this->traderConfig->getEnvironmentPrefix() . date('ymd').'-'. str_pad((string) mt_rand(1, 999), 3, "0"));
        }

        return $orderReference;
    }

    public function nextInvoiceReference(): InvoiceReference
    {
        $createInvoiceReference = new CreateInvoiceReferenceByYearAndMonth($this);

        $invoiceReference = null;
        $append = '';

        while (! $invoiceReference || $this->existsInvoiceReference($invoiceReference)) {
            $invoiceReference = InvoiceReference::fromString($this->traderConfig->getEnvironmentPrefix() . $createInvoiceReference->create()->get() . $append);
            $append = '_' . mt_rand(0, 999);
        }

        return $invoiceReference;
    }

    public function lastInvoiceReference(): ?InvoiceReference
    {
        $lastInvoiceRef = DB::table(static::$orderTable)->orderBy('invoice_ref', 'DESC')->select('invoice_ref')->first()?->invoice_ref;

        if (! $lastInvoiceRef) {
            return null;
        }

        return InvoiceReference::fromString($lastInvoiceRef);
    }

    public function nextShippingReference(): ShippingId
    {
        return ShippingId::fromString((string)Uuid::uuid4());
    }

    public function nextPaymentReference(): PaymentId
    {
        return PaymentId::fromString((string)Uuid::uuid4());
    }

    public function nextShopperReference(): ShopperId
    {
        return ShopperId::fromString((string)Uuid::uuid4());
    }

    public function nextDiscountReference(): DiscountId
    {
        return DiscountId::fromString((string)Uuid::uuid4());
    }

    public function nextLineReference(): LineId
    {
        return LineId::fromString((string) UUid::uuid4());
    }

    public function nextLinePersonalisationReference(): LinePersonalisationId
    {
        return LinePersonalisationId::fromString((string)Uuid::uuid4());
    }

    public function nextLogEntryReference(): OrderEventId
    {
        return OrderEventId::fromString((string)Uuid::uuid4());
    }
}
