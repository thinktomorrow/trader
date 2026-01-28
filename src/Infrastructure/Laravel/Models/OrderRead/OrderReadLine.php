<?php
declare(strict_types=1);

namespace Thinktomorrow\Trader\Infrastructure\Laravel\Models\OrderRead;

use Thinktomorrow\Trader\Application\Common\RendersData;
use Thinktomorrow\Trader\Application\Common\RendersMoney;
use Thinktomorrow\Trader\Domain\Common\Vat\VatPercentage;
use Thinktomorrow\Trader\Domain\Model\Order\Line\PurchasableReference;

abstract class OrderReadLine
{
    use RendersData;
    use RendersMoney;
    use WithLineTotals;
    use WithFormattedLineTotals;

    protected string $line_id;
    protected ?PurchasableReference $purchasableReference;
    protected ?string $variant_id;
    protected ?string $product_id;
    protected array $purchasableData;

    protected int $quantity;
    protected iterable $discounts;
    protected array $data;

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
        } else {
            // Reference does not exist (anymore)
            $line->purchasableReference = null;
        }

        $line->line_id = $state['line_id'];
        $line->quantity = $state['quantity'];
        $line->vatRate = VatPercentage::fromString($state['tax_rate']);
        $line->discounts = $discounts;

        $line->initializeLineTotalsFromState($state);

        $line->personalisations = $personalisations;
        $line->images = [];

        $line->data = json_decode($state['data'], true);
        $line->purchasableData = $line->getData('purchasable_data', []);

        $line->variant_id = $line->purchasableReference?->isVariant() ? $line->purchasableReference->getId() : $line->data('variant_id');
        $line->product_id = $line->data('product_id');

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

    public function getVatPercentage(): VatPercentage
    {
        return $this->vatRate;
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
        if (! $key) {
            return $this->data;
        }

        return $this->data($key, null, $default);
    }
}
