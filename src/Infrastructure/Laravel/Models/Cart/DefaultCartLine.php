<?php
declare(strict_types=1);

namespace Thinktomorrow\Trader\Infrastructure\Laravel\Models\Cart;

use Money\Money;
use Thinktomorrow\Trader\Application\Cart\Read\CartLine;
use Thinktomorrow\Trader\Application\Cart\VariantForCart\VariantForCart;
use Thinktomorrow\Trader\Application\Common\RendersData;
use Thinktomorrow\Trader\Application\Common\RendersMoney;
use Thinktomorrow\Trader\Domain\Common\Cash\Price;

class DefaultCartLine implements CartLine
{
    use RendersData;
    use RendersMoney;

    protected string $line_id;
    protected VariantForCart $variantForCart;
    protected Price $linePrice;
    protected Price $total;
    protected Price $discountTotal;
    protected Money $taxTotal;

    protected int $quantity;
    protected iterable $discounts;
    protected array $data;

    // General flag for all line prices to render with or without tax.
    protected bool $include_tax = true;

    public static function fromMappedData(array $state, VariantForCart $variantForCart, iterable $discounts): static
    {
        $line = new static();

        $line->line_id = $state['line_id'];
        $line->variantForCart = $variantForCart;
        $line->linePrice = $state['linePrice'];
        $line->total = $state['total'];
        $line->discountTotal = $state['discountTotal'];
        $line->taxTotal = $state['taxTotal'];

        $line->quantity = $state['quantity'];
        $line->data = json_decode($state['data'], true);
        $line->discounts = $discounts;

        return $line;
    }

    public function getLineId(): string
    {
        return $this->line_id;
    }

    public function includeTax(bool $includeTax = true): void
    {
        $this->include_tax = $includeTax;
    }

    public function getLinePrice(): string
    {
        return $this->renderMoney(
            $this->include_tax ? $this->linePrice->getIncludingVat() : $this->linePrice->getExcludingVat(),
            $this->getLocale()
        );
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

    public function getImages(): iterable
    {
        return [];
    }

    public function getTitle(): string
    {
        return $this->variantForCart->getTitle();
    }

    public function getDescription(): ?string
    {
        return null;
    }

    public function getDiscounts(): iterable
    {
        return $this->discounts;
    }
}