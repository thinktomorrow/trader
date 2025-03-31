<?php
declare(strict_types=1);

namespace Thinktomorrow\Trader\Infrastructure\Laravel\Models;

use Money\Money;
use Thinktomorrow\Trader\Application\Common\HasLocale;
use Thinktomorrow\Trader\Application\Common\RendersMoney;
use Thinktomorrow\Trader\Application\Order\Grid\OrderGridItem;
use Thinktomorrow\Trader\Domain\Common\Cash\Cash;

class DefaultOrderGridItem implements OrderGridItem
{
    use RendersMoney;
    use HasLocale;

    protected string $order_id;
    protected string $order_reference;
    protected ?string $invoice_reference;
    protected string $state;
    protected ?\DateTime $updated_at;
    protected ?\DateTime $confirmed_at;
    protected ?\DateTime $paid_at;
    protected ?\DateTime $delivered_at;
    protected Money $totalAsMoney;
    protected ?string $customer_id;
    protected array $shopperData;

    final private function __construct()
    {
    }

    public static function fromMappedData(array $state, array $shopperState): static
    {
        $gridItem = new static();

        $gridItem->order_id = $state['order_id'];
        $gridItem->order_reference = $state['order_ref'];
        $gridItem->invoice_reference = $state['invoice_ref'];
        $gridItem->state = $state['order_state'];

        $gridItem->updated_at = isset($state['updated_at']) ? new \DateTime($state['updated_at']) : null;
        $gridItem->confirmed_at = isset($state['confirmed_at']) ? new \DateTime($state['confirmed_at']) : null;
        $gridItem->paid_at = isset($state['paid_at']) ? new \DateTime($state['paid_at']) : null;
        $gridItem->delivered_at = isset($state['delivered_at']) ? new \DateTime($state['delivered_at']) : null;

        $gridItem->totalAsMoney = Cash::make($state['total']);

        $gridItem->shopperData = [
            'email' => $shopperState['email'] ?? null,
            'is_business' => $shopperState['is_business'] ?? false,
            ...(isset($shopperState['data']) ? json_decode($shopperState['data'], true) : []),
        ];
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

    public function getInvoiceReference(): ?string
    {
        return $this->invoice_reference;
    }

    public function getOrderState(): string
    {
        return $this->state;
    }

    public function getUpdatedAt(): ?\DateTime
    {
        return $this->updated_at;
    }

    public function getConfirmedAt(): ?\DateTime
    {
        return $this->confirmed_at;
    }

    public function getPaidAt(): ?\DateTime
    {
        return $this->paid_at;
    }

    public function getDeliveredAt(): ?\DateTime
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
        return $this->shopperData['email'];
    }

    public function isBusiness(): bool
    {
        return ! ! $this->shopperData['is_business'];
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
