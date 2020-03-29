<?php declare(strict_types=1);

namespace Thinktomorrow\Trader\Purchase\Cart\Domain;

use Illuminate\Contracts\Container\Container;
use Optiphar\Cart\Adjusters\PaymentCostAdjuster;
use Optiphar\Cart\Adjusters\ShippingCostAdjuster;
use Thinktomorrow\Trader\Find\Channels\ChannelId;
use Optiphar\Cart\Adjusters\PaymentMethodAdjuster;
use Optiphar\Cart\Adjusters\ShippingMethodAdjuster;
use Thinktomorrow\Trader\Common\Domain\Locales\LocaleId;
use Optiphar\Cart\Adjusters\ItemGuards\AddedAsFreeItemGuard;
use Thinktomorrow\Trader\Purchase\Notes\Domain\NoteCollection;
use Thinktomorrow\Trader\Purchase\Items\Domain\PurchasableItem;
use Thinktomorrow\Trader\Common\Domain\Adjusters\AdjusterStrategy;
use Thinktomorrow\Trader\Purchase\Discounts\Domain\AppliedDiscount;
use Thinktomorrow\Trader\Purchase\Discounts\Domain\AppliedDiscountCollection;

class CartFactory
{
    use AdjusterStrategy;

    /** @var Container */
    private $container;

    protected $adjusters = [

        // TODO: set current channel and locale via cart adjuster...


// Item guards
        AddedAsFreeItemGuard::class,
        StockAndAvailabilityGuard::class,
        BusinessAdjuster::class,

        // Apply the cart discounts.
        DiscountAdjuster::class,

        // General cart adjusters
        CustomerAdjuster::class,
        DefaultShippingCountryAdjuster::class,
        DefaultShippingMethodAdjuster::class,
        ShippingMethodAdjuster::class,
        PaymentMethodAdjuster::class,
        ShippingCostAdjuster::class,
        PaymentCostAdjuster::class,

        ItemPriceDescriptionAdjuster::class,
    ];

    public function __construct(Container $container)
    {
        $this->container = $container;
    }

    public function create(CartReference $cartReference, array $data, array $adjusterInstances): Cart
    {
        $cartItem = $this->createCartItemFromPurchasableItem($id, $purchasableItem);

        $this->applyAdjusters($cartItem, $adjusterInstances);

        return $purchasableItem;
    }

    public function createEmpty(CartReference $cartReference): Cart
    {
        return $this->container->makeWith(Cart::class, [
            'reference' => $cartReference,
            'cartState' => CartState::fromString(CartState::PENDING),
            'cartShipping' => CartShipping::empty(),
            'cartPayment' => CartPayment::empty(),
            'cartCustomer' => CartCustomer::empty(),
            'discounts' => new AppliedDiscountCollection(),
            'notes' => $this->container->make(NoteCollection::class),
            'data' => [
                // TODO: detect and get current channel
                'channel' => ChannelId::fromString('lu'),

                // TODO: detect and get current locale
                'locale' => LocaleId::fromString('nl'),
            ],
        ]);
    }

    protected function createCartItemFromPurchasableItem(string $id, PurchasableItem $purchasableItem): CartItem
    {
        return $this->container->makeWith(CartItem::class, [
            'id'                => $id,
            'purchasableItemId' => $purchasableItem->purchasableItemId(),
            'salePrice'         => $purchasableItem->salePrice(),
            'attributes'        => $purchasableItem->cartItemData(),
        ]);
    }

    private function createCartFromData(array $data): array
    {
        $discounts = collect($data['discounts'])->map(function(array $rawDiscount){
            return new AppliedDiscount(
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
            'items'     => $this->staleItems($data),
            'discounts' => $discounts,
            'shipping'  => new CartShipping($data['shipping']['method'], Money::EUR($data['shipping']['subtotal']), Percentage::fromPercent($data['shipping']['taxrate']), $data['shipping']),
            'payment'   => new CartPayment($data['payment']['method'], Money::EUR($data['payment']['subtotal']), Percentage::fromPercent($data['payment']['taxrate']), $data['payment']),
            'customer'  => new CartCustomer($data['customer']['customerid'], $data['customer']['email'], $data['customer']),
        ]);
    }

}
