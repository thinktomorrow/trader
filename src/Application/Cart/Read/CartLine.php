<?php

namespace Thinktomorrow\Trader\Application\Cart\Read;

use Money\Money;
use Thinktomorrow\Trader\Domain\Common\Vat\VatPercentage;
use Thinktomorrow\Trader\Domain\Model\Order\Line\PurchasableReference;

interface CartLine
{
    public static function fromMappedData(array $state, array $orderState, iterable $discounts, iterable $personalisations): static;

    public function getLineId(): string;

    public function getPurchasableReference(): PurchasableReference;

    public function getUnitPriceExcl(): Money;

    public function getUnitPriceIncl(): Money;

    public function getDiscountPriceExcl(): Money;

    public function getDiscountPriceIncl(): Money;

    public function getTotalPriceExcl(): Money;

    public function getTotalVat(): Money;

    public function getVatRate(): VatPercentage;

    public function getTotalPriceIncl(): Money;

    public function getFormattedUnitPrice(): string;

    public function getFormattedDiscountPrice(): string;

    public function getFormattedTotalPrice(): string;

    public function getFormattedSubtotalPrice(): string;

    public function getFormattedTotalVat(): string;

    public function getFormattedVatRate(): string;

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
