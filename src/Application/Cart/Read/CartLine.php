<?php

namespace Thinktomorrow\Trader\Application\Cart\Read;

use Money\Money;
use Thinktomorrow\Trader\Domain\Model\Order\Line\LinePrice;
use Thinktomorrow\Trader\Domain\Model\Order\Line\PurchasableReference;
use Thinktomorrow\Trader\Domain\Model\Product\Variant\VariantUnitPrice;

interface CartLine
{
    public static function fromMappedData(array $state, array $orderState, iterable $discounts, iterable $personalisations): static;

    public function getLineId(): string;

    public function getPurchasableReference(): PurchasableReference;

    public function getUnitPrice(): string;

    public function getLinePrice(): string;

    public function getUnitPriceAsMoney(): Money;

    public function getLinePriceAsMoney(): Money;

    public function getUnitPriceAsPrice(): VariantUnitPrice;

    public function getLinePriceAsPrice(): LinePrice;

    public function getTotalPrice(): string;

    public function getSubtotalPrice(): string;

    public function getTaxPrice(): string;

    public function getDiscountPrice(): string;

    public function includeTax(bool $includeTax = true): void;

    public function getQuantity(): int;

    public function getTitle(): string;

    public function getDescription(): ?string;

    public function setImages(iterable $images): void;

    public function getImages(): iterable;

    public function getUrl(): string;

    /** @return CartDiscount[] */
    public function getDiscounts(): iterable;

    /** @return CartLinePersonalisation[] */
    public function getPersonalisations(): iterable;

    /**
     * Simple array of the variant taxa related to this line
     * for display purposes. Format of each item should be
     * something like ['label' => '...', 'value', ...]`
     */
    public function getVariants(): array;
}
