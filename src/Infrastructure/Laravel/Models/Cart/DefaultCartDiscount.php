<?php
declare(strict_types=1);

namespace Thinktomorrow\Trader\Infrastructure\Laravel\Models\Cart;

use Thinktomorrow\Trader\Domain\Common\Cash\Percentage;
use Thinktomorrow\Trader\Application\Common\RendersData;
use Thinktomorrow\Trader\Application\Common\RendersMoney;
use Thinktomorrow\Trader\Application\Cart\Read\CartDiscount;
use Thinktomorrow\Trader\Domain\Model\Order\Discount\DiscountTotal;

class DefaultCartDiscount implements CartDiscount
{
    use RendersData;
    use RendersMoney;

    protected DiscountTotal $total;
    protected Percentage $percentage;
    protected string $discount_id;
    protected array $data;

    // General flag for all line prices to render with or without tax.
    protected bool $include_tax = true;

    public static function fromMappedData(array $state, array $cartState): static
    {
        $discount = new static();

        $discount->discount_id = $state['discount_id'];
        $discount->total = $state['total'];
        $discount->percentage = $state['percentage'];
        $discount->data = json_decode($state['data'], true);

        return $discount;
    }

    public function getDiscountId(): string
    {
        return $this->discount_id;
    }

    public function includeTax(bool $includeTax = true): void
    {
        $this->include_tax = $includeTax;
    }

    public function getPrice(): string
    {
        return $this->renderMoney(
            $this->include_tax ? $this->total->getIncludingVat() : $this->total->getExcludingVat(),
            $this->getLocale()
        );
    }

    public function getPercentage(): string
    {
        return $this->percentage->get();
    }

    public function getTitle(): ?string
    {
        return $this->data('title');
    }

    public function getDescription(): ?string
    {
        return $this->data('description');
    }
}