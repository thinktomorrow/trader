<?php
declare(strict_types=1);

namespace Thinktomorrow\Trader\Infrastructure\Laravel\Models;

use Money\Money;
use Thinktomorrow\Trader\Application\Common\HasLocale;
use Thinktomorrow\Trader\Application\Common\RendersMoney;
use Thinktomorrow\Trader\Application\Order\Grid\GridItem;
use Thinktomorrow\Trader\Domain\Common\Cash\Cash;

class DefaultOrderGridItem implements GridItem
{
    use RendersMoney;
    use HasLocale;

    protected string $order_id;
    protected string $order_reference;
    protected string $state;
    protected ?string $confirmed_at;
    protected ?string $paid_at;
    protected ?string $delivered_at;
    protected Money $totalAsMoney;
    protected ?string $shopperEmail;
    protected ?string $customer_id;

    public static function fromMappedData(array $state, array $shopperState): static
    {
        $gridItem = new static();

        $gridItem->order_id = $state['order_id'];
        $gridItem->order_reference = $state['order_ref'];
        $gridItem->state = $state['order_state'];
        $gridItem->confirmed_at = $state['confirmed_at'] ?? null;
        $gridItem->paid_at = $state['paid_at'] ?? null;
        $gridItem->delivered_at = $state['delivered_at'] ?? null;

        $gridItem->totalAsMoney = Cash::make($state['total']);
        $gridItem->shopperEmail = $shopperState['email'] ?? null;
        $gridItem->customer_id = $shopperState['customer_id'] ?? null;


        return $gridItem;
    }

    public function getOrderId(): string
    {
        return $this->order_id;
    }

    public function getOrderReference(): string
    {
        return $this->order_reference;
    }

    public function getOrderState(): string
    {
        return $this->state;
    }

    public function getConfirmedAt(): ?string
    {
        return $this->confirmed_at;
    }

    public function getPaidAt(): ?string
    {
        return $this->paid_at;
    }

    public function getDeliveredAt(): ?string
    {
        return $this->delivered_at;
    }

    public function getTotalPrice(): string
    {
        return $this->renderMoney(
            $this->totalAsMoney,
            $this->getLocale()
        );
    }

    public function getTitle(): string
    {
        return $this->order_reference;
    }

    public function getDescription(): string
    {
        return '';
    }

    public function getUrl(): string
    {
        return '/admin/orders/' . $this->order_id;
    }

    public function getShopperTitle(): string
    {
        return $this->shopperEmail;
    }

    public function hasCustomer(): bool
    {
        return ! is_null($this->customer_id);
    }

    public function getCustomerUrl(): ?string
    {
        if (! $this->customer_id) {
            return null;
        }

        return '/admin/customers/' . $this->customer_id;
    }
}
