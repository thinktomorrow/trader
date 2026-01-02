<?php
declare(strict_types=1);

namespace Thinktomorrow\Trader\Infrastructure\Laravel\Models\OrderRead;

use Assert\Assertion;
use Money\Money;
use Thinktomorrow\Trader\Application\Common\RendersData;
use Thinktomorrow\Trader\Application\Common\RendersMoney;
use Thinktomorrow\Trader\Domain\Common\Vat\VatPercentage;
use Thinktomorrow\Trader\Domain\Model\Order\Line\PurchasableReference;

abstract class OrderReadLine
{
    use RendersData;
    use RendersMoney;
    use WithLineTotals;

    protected string $line_id;
    protected PurchasableReference $purchasableReference;
    protected ?string $variant_id;
    protected ?string $product_id;
    protected array $purchasableData;

    protected int $quantity;
    protected iterable $discounts;
    protected array $data;

    // General flag for all line prices to render with or without tax.
    protected bool $include_tax;
    protected VatPercentage $vatRate;
    protected iterable $images;
    protected iterable $personalisations;

    final public function __construct()
    {
    }

    public static function fromMappedData(array $state, array $orderState, iterable $discounts, iterable $personalisations): static
    {
        $line = new static();

        // variant_id is deprecated, but kept for backward compatibility and current carts
        if (isset($state['purchasable_reference'])) {
            $line->purchasableReference = $state['purchasable_reference'] ? PurchasableReference::fromString($state['purchasable_reference']) : null;
        } elseif (isset($state['variant_id'])) {
            $line->purchasableReference = $state['variant_id'] ? PurchasableReference::fromString('variant@' . $state['variant_id']) : null;
        }

        $line->line_id = $state['line_id'];

        $line->unitPriceExcl = Money::EUR($state['unit_price_excl']);
        $line->unitPriceIncl = Money::EUR($state['unit_price_incl']);
        $line->totalPriceExcl = Money::EUR($state['total_excl']);
        $line->totalPriceIncl = Money::EUR($state['total_incl']);
        $line->totalVat = Money::EUR($state['total_vat']);
        $line->vatRate = VatPercentage::fromString($state['tax_rate']);
        $line->discountPriceExcl = Money::EUR($state['discount_excl']);
        $line->discountPriceIncl = Money::EUR($state['discount_incl']);

        $line->quantity = $state['quantity'];
        $line->discounts = $discounts;
        $line->personalisations = $personalisations;
        $line->images = [];

        $line->data = json_decode($state['data'], true);
        $line->purchasableData = $line->getData('purchasable_data', []);

        Assertion::keyIsset($line->data, 'unit_price_excluding_vat');
        Assertion::keyIsset($line->data, 'unit_price_including_vat');

        $line->variant_id = $line->purchasableReference->isVariant() ? $line->purchasableReference->getId() : $line->data('variant_id');
        $line->product_id = $line->data('product_id');

        // Show prices including tax by default or not
        $line->include_tax = $state['includes_vat'] ?? false;

        return $line;
    }

    public function getLineId(): string
    {
        return $this->line_id;
    }

    public function getPurchasableReference(): PurchasableReference
    {
        return $this->purchasableReference;
    }

    public function getVariantId(): ?string
    {
        return $this->variant_id;
    }

    public function getProductId(): ?string
    {
        return $this->product_id;
    }

    public function includeTax(bool $include_tax = true): void
    {
        $this->include_tax = $include_tax;
    }

    public function getVatRate(): VatPercentage
    {
        return $this->vatRate;
    }

    public function getFormattedUnitPrice(): string
    {
        return $this->renderMoney(
            $this->include_tax ? $this->getUnitPriceIncl() : $this->getUnitPriceExcl(),
            $this->getLocale()
        );
    }

    public function getFormattedDiscountPrice(): string
    {
        return $this->renderMoney(
            $this->include_tax ? $this->getDiscountPriceIncl() : $this->getDiscountPriceExcl(),
            $this->getLocale()
        );
    }

    public function getFormattedTotalPrice(): string
    {
        return $this->renderMoney(
            $this->include_tax ? $this->getTotalPriceIncl() : $this->getTotalPriceExcl(),
            $this->getLocale()
        );
    }

    public function getFormattedSubtotalPrice(): string
    {
        $subtotal = $this->include_tax
            ? $this->getTotalPriceIncl()->subtract($this->getDiscountPriceIncl())
            : $this->getTotalPriceExcl()->subtract($this->getDiscountPriceExcl());

        return $this->renderMoney($subtotal, $this->getLocale());
    }

    public function getFormattedTaxPrice(): string
    {
        return $this->renderMoney(
            $this->getTotalVat(),
            $this->getLocale()
        );
    }

    public function getFormattedVatRate(): string
    {
        return $this->vatRate->get();
    }
//
//    public function getUnitPrice(): string
//    {
//        return $this->renderMoney(
//            $this->getUnitPriceAsMoney(),
//            $this->getLocale()
//        );
//    }

//    public function getUnitPriceAsMoney(): Money
//    {
//        return $this->include_tax ? $this->unitPrice->getIncludingVat() : $this->unitPrice->getExcludingVat();
//    }
//
//    public function getUnitPriceAsPrice(): ItemPrice
//    {
//        return $this->unitPrice;
//    }

//    public function getTotalPrice(): string
//    {
//        return $this->renderMoney(
//            $this->include_tax ? $this->total->getIncludingVat() : $this->total->getExcludingVat(),
//            $this->getLocale()
//        );
//    }
//
//    public function getSubtotalPrice(): string
//    {
//        if ($this->include_tax) {
//            $subtotal = $this->total->getIncludingVat()->subtract($this->discountTotal->getIncludingVat());
//        } else {
//            $subtotal = $this->total->getExcludingVat()->subtract($this->discountTotal->getExcludingVat());
//        }
//
//        return $this->renderMoney($subtotal, $this->getLocale());
//    }
//
//    public function getTaxPrice(): string
//    {
//        return $this->renderMoney(
//            $this->taxTotal,
//            $this->getLocale()
//        );
//    }
//
//    public function getDiscountPrice(): string
//    {
//        return $this->renderMoney(
//            $this->include_tax ? $this->discountTotal->getIncludingVat() : $this->discountTotal->getExcludingVat(),
//            $this->getLocale()
//        );
//    }

//    public function getLinePrice(): string
//    {
//        throw new \Exception('getLinePrice is deprecated, use getUnitPrice instead.');
//    }
//
//    public function getLinePriceAsMoney(): Money
//    {
//        throw new \Exception('getLinePriceAsMoney is deprecated, use getUnitPriceAsMoney instead.');
//    }
//
//    public function getLinePriceAsPrice(): LinePrice
//    {
//        throw new \Exception('getLinePriceAsPrice is deprecated, use getUnitPriceAsPrice instead.');
//    }

    public function getQuantity(): int
    {
        return $this->quantity;
    }

    public function setImages(iterable $images): void
    {
        $this->images = $images;
    }

    public function getImages(): iterable
    {
        return $this->images;
    }

    public function getTitle(): string
    {
        return $this->data('title', null, '');
    }

    public function getDescription(): ?string
    {
        return $this->data('description');
    }

    public function getDiscounts(): iterable
    {
        return $this->discounts;
    }

    public function getPersonalisations(): iterable
    {
        return $this->personalisations;
    }

    public function getData(?string $key = null, $default = null): mixed
    {
        if (!$key) {
            return $this->data;
        }

        return $this->data($key, null, $default);
    }
}
