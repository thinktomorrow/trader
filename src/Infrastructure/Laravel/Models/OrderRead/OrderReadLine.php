<?php
declare(strict_types=1);

namespace Thinktomorrow\Trader\Infrastructure\Laravel\Models\OrderRead;

use Assert\Assertion;
use Money\Money;
use Thinktomorrow\Trader\Application\Common\RendersData;
use Thinktomorrow\Trader\Application\Common\RendersMoney;
use Thinktomorrow\Trader\Domain\Common\Cash\Cash;
use Thinktomorrow\Trader\Domain\Common\Price\Price;
use Thinktomorrow\Trader\Domain\Model\Order\Line\LinePrice;
use Thinktomorrow\Trader\Domain\Model\Product\Variant\VariantUnitPrice;

abstract class OrderReadLine
{
    use RendersData;
    use RendersMoney;

    protected string $line_id;
    protected ?string $variant_id;
    protected string $product_id;
    protected LinePrice $linePrice;
    protected VariantUnitPrice $unitPrice;
    protected Price $total;
    protected Price $discountTotal;
    protected Money $taxTotal;

    protected int $quantity;
    protected iterable $discounts;
    protected array $data;

    // General flag for all line prices to render with or without tax.
    protected bool $include_tax;
    protected iterable $images;
    protected iterable $personalisations;

    final public function __construct()
    {
    }

    public static function fromMappedData(array $state, array $orderState, iterable $discounts, iterable $personalisations): static
    {
        $line = new static();

        $line->line_id = $state['line_id'];
        $line->variant_id = $state['variant_id'] ?: null;
        $line->linePrice = $state['linePrice'];
        $line->total = $state['total'];
        $line->discountTotal = $state['discountTotal'];
        $line->taxTotal = $state['taxTotal'];

        $line->quantity = $state['quantity'];
        $line->discounts = $discounts;
        $line->personalisations = $personalisations;
        $line->images = [];


        $line->data = json_decode($state['data'], true);

        Assertion::keyIsset($line->data, 'product_id');
        Assertion::keyIsset($line->data, 'unit_price_excluding_vat');
        Assertion::keyIsset($line->data, 'unit_price_including_vat');

        $line->product_id = $line->data('product_id');
        $line->unitPrice = VariantUnitPrice::fromMoney(
            Cash::make($line->linePrice->includesVat() ? $line->data('unit_price_including_vat') : $line->data('unit_price_excluding_vat')),
            $line->linePrice->getVatPercentage(),
            $line->linePrice->includesVat()
        );

        $line->include_tax = $line->linePrice->includesVat();

        return $line;
    }

    public function getLineId(): string
    {
        return $this->line_id;
    }

    public function getVariantId(): ?string
    {
        return $this->variant_id;
    }

    public function getProductId(): string
    {
        return $this->product_id;
    }

    public function includeTax(bool $include_tax = true): void
    {
        $this->include_tax = $include_tax;
    }

    public function getUnitPrice(): string
    {
        return $this->renderMoney(
            $this->getUnitPriceAsMoney(),
            $this->getLocale()
        );
    }

    public function getUnitPriceAsMoney(): Money
    {
        return $this->include_tax ? $this->unitPrice->getIncludingVat() : $this->unitPrice->getExcludingVat();
    }

    public function getUnitPriceAsPrice(): VariantUnitPrice
    {
        return $this->unitPrice;
    }

    public function getLinePrice(): string
    {
        return $this->renderMoney(
            $this->getLinePriceAsMoney(),
            $this->getLocale()
        );
    }

    public function getLinePriceAsMoney(): Money
    {
        return $this->include_tax ? $this->linePrice->getIncludingVat() : $this->linePrice->getExcludingVat();
    }

    public function getLinePriceAsPrice(): LinePrice
    {
        return $this->linePrice;
    }

    public function getTotalPrice(): string
    {
        return $this->renderMoney(
            $this->include_tax ? $this->total->getIncludingVat() : $this->total->getExcludingVat(),
            $this->getLocale()
        );
    }

    public function getSubtotalPrice(): string
    {
        $subtotal = $this->total->subtractDifferent($this->discountTotal);

        // TODO: wat met verschillende taxrates...

        return $this->renderMoney(
            $this->include_tax ? $subtotal->getIncludingVat() : $subtotal->getExcludingVat(),
            $this->getLocale()
        );
    }

    public function getTaxPrice(): string
    {
        return $this->renderMoney(
            $this->taxTotal,
            $this->getLocale()
        );
    }

    public function getDiscountPrice(): string
    {
        return $this->renderMoney(
            $this->include_tax ? $this->discountTotal->getIncludingVat() : $this->discountTotal->getExcludingVat(),
            $this->getLocale()
        );
    }

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
        return $this->data('title', null, $this->variant_id);
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
}
