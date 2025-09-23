<?php
declare(strict_types=1);

namespace Thinktomorrow\Trader\Domain\Model\Taxon;

use Thinktomorrow\Trader\Domain\Common\Locale;
use Thinktomorrow\Trader\Domain\Model\Taxon\Events\TaxonKeyUpdated;
use Thinktomorrow\Trader\Domain\Model\Taxon\Exceptions\InvalidTaxonIdOnTaxonKey;

trait HasTaxonKeys
{
    /** @var TaxonKey[] */
    private array $taxonKeys = [];

    /** @return TaxonKey[] */
    public function getTaxonKeys(): array
    {
        return $this->taxonKeys;
    }

    public function hasTaxonKeyId(TaxonKeyId $taxonKeyId): bool
    {
        foreach ($this->taxonKeys as $taxonKey) {
            if ($taxonKey->getKey()->equals($taxonKeyId)) {
                return true;
            }
        }

        return false;
    }

    public function updateTaxonKeys(array $taxonKeys): void
    {
        foreach ($taxonKeys as $taxonKey) {
            $this->addOrUpdateTaxonKey($taxonKey);
        }
    }

    /**
     * Add the keys if for the given locale no key is present yet,
     * else we update since we have one key per locale
     *
     * @param TaxonKey $taxonKey
     * @return void
     */
    private function addOrUpdateTaxonKey(TaxonKey $taxonKey): void
    {
        $this->assertMatchingTaxonId($taxonKey);

        if (($existingKey = $this->findTaxonKeyByLocale($taxonKey->getLocale())) && ! $existingKey->getKey()->equals($taxonKey->getKey())) {

            $oldKeyId = $existingKey->getKey();

            // Set to array again to ensure the updated key is stored
            $this->taxonKeys = array_map(function (TaxonKey $key) use ($taxonKey) {
                return $key->getLocale()->equals($taxonKey->getLocale()) ? $key->changeKey($taxonKey->getKey()) : $key;
            }, $this->taxonKeys);

            $this->recordEvent(new TaxonKeyUpdated($this->taxonId, $taxonKey->getLocale(), $oldKeyId, $taxonKey->getKey()));

            return;
        }

        // no existing key for this locale → add new
        $this->taxonKeys[] = $taxonKey;
    }

    private function assertMatchingTaxonId(TaxonKey $taxonKey): void
    {
        if (! $taxonKey->taxonId->equals($this->taxonId)) {
            throw new InvalidTaxonIdOnTaxonKey(sprintf(
                'Cannot add or update TaxonKey. Passed TaxonKey has TaxonId [%s] that doesn\'t match with TaxonId [%s].',
                $taxonKey->taxonId->get(),
                $this->taxonId->get()
            ));
        }
    }

    private function findTaxonKeyByLocale(Locale $locale): ?TaxonKey
    {
        foreach ($this->taxonKeys as $existingTaxonKey) {
            if ($existingTaxonKey->getLocale()->equals($locale)) {
                return $existingTaxonKey;
            }
        }

        return null;
    }
}
