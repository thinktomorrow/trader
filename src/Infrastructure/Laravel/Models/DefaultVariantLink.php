<?php

declare(strict_types=1);

namespace Thinktomorrow\Trader\Infrastructure\Laravel\Models;

use Thinktomorrow\Trader\Application\Common\HasLocale;
use Thinktomorrow\Trader\Application\Common\RendersData;
use Thinktomorrow\Trader\Application\Product\Taxa\ProductTaxonItem;
use Thinktomorrow\Trader\Application\Product\VariantLinks\VariantLink;
use Thinktomorrow\Trader\Domain\Model\Product\Variant\Variant;
use Thinktomorrow\Trader\Domain\Model\Product\Variant\VariantState;

class DefaultVariantLink implements VariantLink
{
    use HasLocale;
    use RendersData;

    protected bool $isActive = false;

    protected ?Variant $variant;

    protected string $groupId;

    protected iterable $images = [];

    protected array $data;

    private function __construct(string $groupId, ?Variant $variant, array $data)
    {
        $this->groupId = $groupId;
        $this->variant = $variant;
        $this->data = $data;
    }

    public static function fromVariantProperty(ProductTaxonItem $property, ?Variant $variant = null): static
    {
        return new static($property->getTaxonomyId(), $variant, [
            'group_label' => $property->getTaxonomyLabel(),
            'label' => $property->getLabel(),
            'taxonomy_data' => $property->getData('taxonomy_data'),
            'taxon_data' => $property->getData('taxon_data'),
        ]);
    }

    public static function fromVariant(Variant $variant): static
    {
        $label = $variant->getData('option_title', $variant->getData('title', $variant->getSku()));

        return new static('variants', $variant, [
            'group_label' => null,
            'label' => $label,
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

        return '/'.$this->variant->variantId->get();
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

    public function setImages(iterable $images): void
    {
        $this->images = $images;
    }

    public function getImages(): iterable
    {
        return $this->images;
    }

    public function getData(?string $key = null, $default = null): mixed
    {
        return $this->data($key, null, $default);
    }
}
