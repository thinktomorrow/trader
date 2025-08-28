<?php
declare(strict_types=1);

namespace Thinktomorrow\Trader\Infrastructure\Laravel\Models;

use Thinktomorrow\Trader\Application\Common\HasLocale;
use Thinktomorrow\Trader\Application\Common\RendersData;
use Thinktomorrow\Trader\Application\Product\VariantLinks\VariantLink;
use Thinktomorrow\Trader\Domain\Model\Product\ProductTaxa\VariantProperty;
use Thinktomorrow\Trader\Domain\Model\Product\Variant\Variant;
use Thinktomorrow\Trader\Domain\Model\Product\Variant\VariantState;
use Thinktomorrow\Trader\Domain\Model\Product\VariantTaxa\VariantProperty as VariantVariantProperty;

class DefaultVariantLink implements VariantLink
{
    use HasLocale;
    use RendersData;

    protected bool $isActive = false;
    protected ?Variant $variant;
    protected string $groupId;
    protected array $data;

    private function __construct(string $groupId, ?Variant $variant, array $data)
    {
        $this->groupId = $groupId;
        $this->variant = $variant;
        $this->data = $data;
    }

    public static function fromVariantProperty(VariantProperty|VariantVariantProperty $property, ?Variant $variant = null): static
    {
        return new static($property->taxonId->get(), $variant, [
            'group_label' => $property->getData('label'),
            'label' => $property->getData('value'),
        ]);
    }

    public static function fromVariant(Variant $variant): static
    {
        return new static('variants', $variant, [
            'group_label' => null,
            'label' => $variant->getData('option_title', $variant->getData('title')),
        ]);
    }

    public function getGroupId(): string
    {
        return $this->groupId;
    }

    public function getGroupLabel(): string
    {
        return $this->dataAsPrimitive('group_label', null, '');
    }

    public function getLabel(): string
    {
        return $this->dataAsPrimitive('label', null, '');
    }

    public function getUrl(): ?string
    {
        if (! $this->variant) {
            return null;
        }

        return '/' . $this->variant->variantId->get();
    }

    public function isVariantAvailable(): bool
    {
        if (! $this->variant) {
            return false;
        }

        return in_array($this->variant->getState(), VariantState::availableStates());
    }

    public function isActive(): bool
    {
        return $this->isActive;
    }

    public function markActive(): void
    {
        $this->isActive = true;
    }
}
