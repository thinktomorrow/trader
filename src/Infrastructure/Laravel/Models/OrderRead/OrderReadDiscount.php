<?php
declare(strict_types=1);

namespace Thinktomorrow\Trader\Infrastructure\Laravel\Models\OrderRead;

use Thinktomorrow\Trader\Application\Common\RendersData;
use Thinktomorrow\Trader\Application\Common\RendersMoney;
use Thinktomorrow\Trader\Domain\Common\Cash\Percentage;
use Thinktomorrow\Trader\Domain\Common\Price\DiscountPrice;

abstract class OrderReadDiscount
{
    use RendersData;
    use RendersMoney;

    protected DiscountPrice $total;
    protected Percentage $percentage;
    protected string $discount_id;
    protected array $data;

    final public function __construct()
    {
    }

    public static function fromMappedData(array $state, array $orderState): static
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

    public function getDiscountPrice(): DiscountPrice
    {
        return $this->total;
    }

    public function getFormattedDiscountPriceExcl(): string
    {
        return $this->renderMoney(
            $this->getDiscountPrice()->getExcludingVat(),
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

    public function isCouponCodeBased(): bool
    {
        return $this->data('coupon_code') !== null;
    }

    public function getCouponCode(): ?string
    {
        return $this->data('coupon_code');
    }

    public function getData(string $key, $default = null): mixed
    {
        return $this->data($key, null, $default);
    }
}
