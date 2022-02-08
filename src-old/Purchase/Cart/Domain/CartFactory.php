<?php declare(strict_types=1);

namespace Purchase\Cart\Domain;

use Find\Channels\ChannelId;
use Common\Notes\NoteCollection;
use Common\Domain\Locales\LocaleId;
use Purchase\Items\Domain\PurchasableItem;
use Illuminate\Contracts\Container\Container;
use Common\Domain\Adjusters\AdjusterStrategy;
use Purchase\Discounts\Domain\AppliedDiscount;
use Thinktomorrow\Trader\Purchase\Cart\Domain\Money;
use Thinktomorrow\Trader\Purchase\Cart\Domain\TypeKey;
use Purchase\Cart\Domain\Adjusters\PaymentCostAdjuster;
use Purchase\Cart\Domain\Adjusters\ShippingCostAdjuster;
use Purchase\Discounts\Domain\AppliedDiscountCollection;
use Purchase\Cart\Domain\Adjusters\PaymentMethodAdjuster;
use Thinktomorrow\Trader\Purchase\Cart\Domain\Percentage;
use Thinktomorrow\Trader\Purchase\Cart\Domain\DiscountId;
use Purchase\Cart\Domain\Adjusters\ShippingMethodAdjuster;
use Purchase\Cart\Domain\Adjusters\ItemGuards\AddedAsFreeItemGuard;
use function collect;

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
