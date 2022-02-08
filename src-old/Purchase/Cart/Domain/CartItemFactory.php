<?php declare(strict_types=1);

namespace Purchase\Cart\Domain;

use Assert\Assertion;
use Purchase\Items\Domain\PurchasableItem;
use Common\Domain\Adjusters\AdjusterStrategy;
use Illuminate\Contracts\Container\Container;

class CartItemFactory
{
    use AdjusterStrategy;

    /** @var Container */
    private $container;

    protected $adjusters = [

    ];

    public function __construct(Container $container)
    {
        $this->container = $container;
    }

    public function create(string $id, PurchasableItem $purchasableItem, array $adjusterInstances): CartItem
    {
        $cartItem = $this->createCartItemFromPurchasableItem($id, $purchasableItem);

        $this->applyAdjusters($cartItem, $adjusterInstances);

        return $cartItem;
    }

    public function createMany(array $purchasableItems, array $adjusterInstances): CartItemCollection
    {
        Assertion::allIsInstanceOf($purchasableItems, PurchasableItem::class);

        return $this->container->makeWith(CartItemCollection::class, array_map(function (PurchasableItem $purchasableItem) use ($adjusterInstances) {
            return $this->create($purchasableItem->purchasableItemId()->get(), $purchasableItem, $adjusterInstances);
        }, $purchasableItems));
    }

    protected function createCartItemFromPurchasableItem(string $id, PurchasableItem $purchasableItem): CartItem
    {
        return $this->container->makeWith(CartItem::class, [
            'id'                => $id,
            'purchasableItemId' => $purchasableItem->purchasableItemId(),
            'salePrice'         => $purchasableItem->salePrice(),
            'taxRate'   => $purchasableItem->taxRate(),
            'attributes'        => $purchasableItem->cartItemData(),
        ]);

        // string $id,
        //        PurchasableItemId $purchasableItemId,
        //        Money $salePrice,
        //        TaxRate $taxRate,
        //        AppliedDiscountCollection $discounts,
        //        NoteCollection $notes,
        //        array $attributes,
        //        int $quantity = 1
    }
}
