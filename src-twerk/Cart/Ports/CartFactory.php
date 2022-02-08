<?php
declare(strict_types=1);

namespace Thinktomorrow\Trader\Cart\Ports;

use Illuminate\Contracts\Container\Container;
use Money\Money;
use Thinktomorrow\Trader\Common\Cash\Percentage;
use Thinktomorrow\Trader\Common\Domain\ChannelId;
use Thinktomorrow\Trader\Common\Domain\Locale;
use Thinktomorrow\Trader\Order\Domain\Order;
use Thinktomorrow\Trader\Order\Domain\OrderCustomer;
use Thinktomorrow\Trader\Order\Domain\OrderPayment;
use Thinktomorrow\Trader\Order\Domain\OrderShipping;

class CartFactory
{
    /**
     * Strategy of different actions on the cart in order to shape it to the current context. The
     * sequence is important as it is handled first to last and each adjuster can influence
     * the cart state as it is passed on to the next adjuster.
     *
     * @var array
     */
    private $adjusters = [
        // Item guards
//        AddedAsFreeItemGuard::class,
//        StockAndAvailabilityGuard::class,
//        MedicineGuard::class,
//        NotEligibleForDiscountGuard::class,
//        CatalogGuard::class,
//        DutchTaxRateAdjuster::class,
//        BusinessAdjuster::class,
//
//        // Apply the cart discounts.
//        DiscountAdjuster::class,
//
//        // General cart adjusters
//        CustomerAdjuster::class,
//        DefaultShippingCountryAdjuster::class,
//        DefaultShippingMethodAdjuster::class,
//        ShippingMethodAdjuster::class,
//        PaymentMethodAdjuster::class,
//        ShippingCostAdjuster::class,
//        PaymentCostAdjuster::class,
//        MailchimpCampaignAdjuster::class,
//
//        ItemPriceDescriptionAdjuster::class,
    ];

    private Container $container;

    private CartItemsFactory $cartItemsFactory;

    public function __construct(Container $container, CartItemsFactory $cartItemsFactory)
    {
        $this->container = $container;
        $this->cartItemsFactory = $cartItemsFactory;
    }

    public function create(Order $order): Order
    {
        $order->getOrderState()->assertCartState();

        // Use cart adjusters to update items, discounts, shipping, payment, ...
        foreach ($this->adjusters as $adjuster) {
            $this->container->make($adjuster)->adjust($order);
        }

        return $order;

//        $orderProducts = $this->orderProductRepository->getByOrder($orderReference);
//        $discounts = [];
//        $notes = [];
//
//        $order = $this->container->make(Order::class, [
//            $orderReference,
//            $orderState,
//            $orderProducts,
//            new OrderShipping(
//                $data['shipping']['method'],
//                Money::EUR($data['shipping']['subtotal']),
//                Percentage::fromInteger($data['shipping']['taxrate']),
//                $data['shipping']
//            ),
//            new OrderPayment(
//                $data['payment']['method'],
//                Money::EUR($data['payment']['subtotal']),
//                Percentage::fromPercent($data['payment']['taxrate']), $data['payment']
//            ),
//            new OrderCustomer(
//                $data['customer']['customerid'],
//                $data['customer']['email'],
//                $data['customer']
//            ),
//            $discounts,
//            $notes,
//            [
//                // TODO: detect and get current channel
//                'channel' => ChannelId::fromString('lu'),
//
//                // TODO: detect and get current locale
//                'locale' => Locale::default(),
//            ],

//            'items'    => '',
//            'shipping'  => new CartShipping($data['shipping']['method'], Money::EUR($data['shipping']['subtotal']), Percentage::fromPercent($data['shipping']['taxrate']), $data['shipping']),
//            'payment'   => new CartPayment($data['payment']['method'], Money::EUR($data['payment']['subtotal']), Percentage::fromPercent($data['payment']['taxrate']), $data['payment']),
//            'customer'  => new CartCustomer($data['customer']['customerid'], $data['customer']['email'], $data['customer']),
//            'discounts' => collect(),
//            'notes'     => new CartNotes(),
//        ]);
    }

//    public function createEmpty(CartReference $cartReference): Cart
//    {
//        return Cart::fromData($cartReference,  new CartItems());
//    }

    private function freshItems(array $data): CartItems
    {
        return $this->cartItemsFactory->create($data['items']);
    }

    private function freshData(array $data): array
    {
        // Items are already fetched as separate parameter
        unset($data['items']);

        return array_merge($data, [
            'shipping' => new OrderShipping($data['shipping']['method'], Money::EUR($data['shipping']['subtotal']), Percentage::fromPercent($data['shipping']['taxrate']), $data['shipping']),
            'payment' => new OrderPayment($data['payment']['method'], Money::EUR($data['payment']['subtotal']), Percentage::fromPercent($data['payment']['taxrate']), $data['payment']),
            'customer' => new OrderCustomer($data['customer']['customerid'], $data['customer']['email'], $data['customer']),
            'discounts' => collect(),
            'notes' => new CartNotes(),
        ]);
    }

    private function staleData(array $data): array
    {
        $discounts = collect($data['discounts'])->map(function (array $rawDiscount) {
            return new CartDiscount(
                DiscountId::fromString($rawDiscount['discountid']),
                TypeKey::fromString($rawDiscount['typekey']),
                Money::EUR($rawDiscount['total']),
                Percentage::fromPercent($rawDiscount['taxrate']),
                Money::EUR($rawDiscount['basetotal']),
                Percentage::fromPercent($rawDiscount['percentage']),
                $rawDiscount['data']
            );
        });

        return array_merge($data, [
            'items' => $this->staleItems($data),
            'discounts' => $discounts,
            'shipping' => new OrderShipping($data['shipping']['method'], Money::EUR($data['shipping']['subtotal']), Percentage::fromPercent($data['shipping']['taxrate']), $data['shipping']),
            'payment' => new OrderPayment($data['payment']['method'], Money::EUR($data['payment']['subtotal']), Percentage::fromPercent($data['payment']['taxrate']), $data['payment']),
            'customer' => new OrderCustomer($data['customer']['customerid'], $data['customer']['email'], $data['customer']),
        ]);
    }

    private function staleItems(array $data): CartItems
    {
        $items = collect($data['items'])->map(function (array $rawItem) {
            $quantity = $rawItem['quantity'];
            unset($rawItem['quantity']);

            $rawDiscounts = $rawItem['discounts'];
            unset($rawItem['discounts']);

            $item = new CartItem(array_merge($rawItem, [
                'saleprice' => Money::EUR($rawItem['saleprice']),
                'price' => Money::EUR($rawItem['price']),
                'taxrate' => Percentage::fromPercent($rawItem['taxrate']),
                'product' => $this->productReadRepository->find($rawItem['product_id']),
            ]), $quantity);

            foreach ($rawDiscounts as $rawDiscount) {
                $item->addDiscount(new CartDiscount(
                    DiscountId::fromString($rawDiscount['discountid']),
                    TypeKey::fromString($rawDiscount['typekey']),
                    Money::EUR($rawDiscount['total']),
                    Percentage::fromPercent($rawDiscount['taxrate']),
                    Money::EUR($rawDiscount['basetotal']),
                    Percentage::fromPercent($rawDiscount['percentage']),
                    $rawDiscount['data']
                ));
            }

            return $item;
        });

        return new CartItems($items->all());
    }
}
