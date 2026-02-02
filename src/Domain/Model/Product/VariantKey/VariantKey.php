<?php
declare(strict_types=1);

namespace Thinktomorrow\Trader\Domain\Model\Product\VariantKey;

use Thinktomorrow\Trader\Domain\Common\Entity\ChildEntity;
use Thinktomorrow\Trader\Domain\Common\Locale;
use Thinktomorrow\Trader\Domain\Model\Product\Variant\VariantId;

class VariantKey implements ChildEntity
{
    public readonly VariantId $variantId;
    public VariantKeyId $variantKeyId;
    private Locale $locale;

    private function __construct()
    {
    }

    public static function create(VariantId $variantId, VariantKeyId $key, Locale $locale): static
    {
        $variantKey = new static();

        $variantKey->variantId = $variantId;
        $variantKey->variantKeyId = $key;
        $variantKey->locale = $locale;

        return $variantKey;
    }

    public static function temp(VariantKeyId $key, Locale $locale): static
    {
        $variantKey = new static();

        $variantKey->variantKeyId = $key;
        $variantKey->locale = $locale;

        return $variantKey;
    }

    public function getKey(): VariantKeyId
    {
        return $this->variantKeyId;
    }

    public function changeKey(VariantKeyId $newKey): self
    {
        return static::create(
            $this->variantId,
            $newKey,
            $this->locale
        );
    }

    public function getLocale(): Locale
    {
        return $this->locale;
    }

    public function getMappedData(): array
    {
        return [
            'variant_id' => $this->variantId->get(),
            'key' => $this->variantKeyId->get(),
            'locale' => $this->locale->get(),
        ];
    }

    public static function fromMappedData(array $state, array $aggregateState): static
    {
        $variantKey = new static();

        $variantKey->variantId = VariantId::fromString($aggregateState['variant_id']);
        $variantKey->variantKeyId = VariantKeyId::fromString($state['key']);
        $variantKey->locale = Locale::fromString($state['locale']);

        return $variantKey;
    }
}
