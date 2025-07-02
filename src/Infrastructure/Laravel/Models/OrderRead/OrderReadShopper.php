<?php
declare(strict_types=1);

namespace Thinktomorrow\Trader\Infrastructure\Laravel\Models\OrderRead;

use Thinktomorrow\Trader\Application\Common\RendersData;
use Thinktomorrow\Trader\Domain\Common\Locale;
use Thinktomorrow\Trader\Domain\Model\VatNumber\VatNumberValidationState;

abstract class OrderReadShopper
{
    use RendersData;

    protected string $shopper_id;
    protected ?string $customer_id;
    protected string $email;
    protected bool $is_business;
    protected Locale $shopperLocale;
    protected array $data;

    final public function __construct()
    {
    }

    public static function fromMappedData(array $state, array $orderState): static
    {
        $shopper = new static();

        $shopper->shopper_id = $state['shopper_id'];
        $shopper->customer_id = $state['customer_id'];
        $shopper->email = $state['email'];
        $shopper->is_business = $state['is_business'];
        $shopper->shopperLocale = Locale::fromString($state['locale']);
        $shopper->data = json_decode($state['data'], true);

        return $shopper;
    }

    public function getShopperId(): string
    {
        return $this->shopper_id;
    }

    public function getCustomerId(): ?string
    {
        return $this->customer_id;
    }

    public function getEmail(): string
    {
        return $this->email;
    }

    public function isCustomer(): bool
    {
        return ! is_null($this->customer_id);
    }

    public function isGuest(): bool
    {
        return ! $this->isCustomer();
    }

    public function isBusiness(): bool
    {
        return $this->is_business;
    }

    public function isVatExempt(): bool
    {
        return $this->dataAsPrimitive('is_vat_exempt', null, false);
    }

    public function getVatNumber(): ?string
    {
        return $this->dataAsPrimitive('vat_number');
    }

    public function getVatNumberCountry(): ?string
    {
        return $this->dataAsPrimitive('vat_number_country');
    }

    public function isVatNumberValid(): bool
    {
        return ! ! $this->dataAsPrimitive('vat_number_valid');
    }

    public function getVatNumberState(): VatNumberValidationState
    {
        if (! $state = $this->dataAsPrimitive('vat_number_state')) {
            return VatNumberValidationState::unknown;
        }

        return VatNumberValidationState::from($state);
    }

    public function getShopperLocale(): Locale
    {
        return $this->shopperLocale;
    }
}
