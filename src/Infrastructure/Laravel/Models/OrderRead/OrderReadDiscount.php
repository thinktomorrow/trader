<?php
declare(strict_types=1);

namespace Thinktomorrow\Trader\Infrastructure\Laravel\Models\OrderRead;

use Money\Money;
use Thinktomorrow\Trader\Application\Common\RendersData;
use Thinktomorrow\Trader\Application\Common\RendersMoney;
use Thinktomorrow\Trader\Domain\Common\Cash\Percentage;
use Thinktomorrow\Trader\Domain\Common\Price\DefaultDiscountPrice;
use Thinktomorrow\Trader\Domain\Common\Price\DefaultItemDiscountPrice;
use Thinktomorrow\Trader\Domain\Common\Price\DiscountPrice;
use Thinktomorrow\Trader\Domain\Common\Price\ItemDiscountPrice;
use Thinktomorrow\Trader\Domain\Common\Vat\VatPercentage;
use Thinktomorrow\Trader\Domain\Model\Order\Discount\DiscountableType;

abstract class OrderReadDiscount
{
    use RendersData;
    use RendersMoney;

    protected DiscountPrice|ItemDiscountPrice $discountPrice;
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
        $discount->percentage = $state['percentage'];
        $discount->data = json_decode($state['data'], true);

        if ($state['discountable_type'] == DiscountableType::line->value) {

            if (isset($state['total_incl']) && $state['total_incl'] !== null) {
                $discount->discountPrice = DefaultItemDiscountPrice::fromIncludingVat(
                    Money::EUR($state['total_incl']),
                    VatPercentage::fromString($state['vat_rate'])
                );
            } else {
                $discount->discountPrice = DefaultItemDiscountPrice::fromExcludingVat(
                    Money::EUR($state['total_excl']),
                    VatPercentage::fromString($state['vat_rate'])
                );
            }

        } else {
            $discount->discountPrice = DefaultDiscountPrice::fromExcludingVat(Money::EUR($state['total_excl']));
        }

        return $discount;
    }

    public function getDiscountId(): string
    {
        return $this->discount_id;
    }

    public function getDiscountPrice(): DiscountPrice|ItemDiscountPrice
    {
        return $this->discountPrice;
    }

    public function getFormattedDiscountPriceExcl(): string
    {
        return $this->renderMoney(
            $this->getDiscountPrice()->getExcludingVat(),
            $this->getLocale()
        );
    }

    public function getFormattedDiscountPriceIncl(): ?string
    {
        if (! $this->getDiscountPrice() instanceof ItemDiscountPrice) {
            return null;
        }

        return $this->renderMoney(
            $this->getDiscountPrice()->getIncludingVat(),
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
