<?php
declare(strict_types=1);

namespace Thinktomorrow\Trader\Domain\Model\Product\VariantKey;

use Thinktomorrow\Trader\Domain\Common\Locale;
use Thinktomorrow\Trader\Domain\Model\Product\Events\VariantKeyCreated;
use Thinktomorrow\Trader\Domain\Model\Product\Events\VariantKeyUpdated;
use Thinktomorrow\Trader\Domain\Model\Product\Exceptions\InvalidVariantIdOnVariantKey;

trait HasVariantKeys
{
    /** @var VariantKey[] */
    private array $variantKeys = [];

    /** @return VariantKey[] */
    public function getVariantKeys(): array
    {
        return $this->variantKeys;
    }

    public function hasVariantKeyId(VariantKeyId $variantKeyId): bool
    {
        foreach ($this->variantKeys as $variantKey) {
            if ($variantKey->getKey()->equals($variantKeyId)) {
                return true;
            }
        }

        return false;
    }

    public function updateVariantKeys(array $variantKeys): void
    {
        foreach ($variantKeys as $variantKey) {
            $this->addOrUpdateVariantKey($variantKey);
        }
    }

    /**
     * Add the keys if for the given locale no key is present yet,
     * else we update since we have one key per locale
     *
     * @param VariantKey $variantKey
     * @return void
     */
    private function addOrUpdateVariantKey(VariantKey $variantKey): void
    {
        $this->assertMatchingVariantId($variantKey);

        if (($existingKey = $this->findVariantKeyByLocale($variantKey->getLocale())) && !$existingKey->getKey()->equals($variantKey->getKey())) {

            $oldKeyId = $existingKey->getKey();

            // Set to array again to ensure the updated key is stored
            $this->variantKeys = array_map(function (VariantKey $key) use ($variantKey) {
                return $key->getLocale()->equals($variantKey->getLocale()) ? $key->changeKey($variantKey->getKey()) : $key;
            }, $this->variantKeys);

            $this->recordEventForAggregate(new VariantKeyUpdated($this->variantId, $variantKey->getLocale(), $oldKeyId, $variantKey->getKey()));

            return;
        }

        // no existing key for this locale → add new
        $this->variantKeys[] = $variantKey;

        $this->recordEventForAggregate(new VariantKeyCreated($this->variantId, $variantKey->getLocale(), $variantKey->getKey()));
    }

    private function assertMatchingVariantId(VariantKey $variantKey): void
    {
        if (!$variantKey->variantId->equals($this->variantId)) {
            throw new InvalidVariantIdOnVariantKey(sprintf(
                'Cannot add or update VariantKey. Passed VariantKey has VariantId [%s] that doesn\'t match with VariantId [%s].',
                $variantKey->variantId->get(),
                $this->variantId->get()
            ));
        }
    }

    private function findVariantKeyByLocale(Locale $locale): ?VariantKey
    {
        foreach ($this->variantKeys as $existingVariantKey) {
            if ($existingVariantKey->getLocale()->equals($locale)) {
                return $existingVariantKey;
            }
        }

        return null;
    }
}
