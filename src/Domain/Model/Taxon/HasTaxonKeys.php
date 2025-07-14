<?php
declare(strict_types=1);

namespace Thinktomorrow\Trader\Domain\Model\Taxon;

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
            if ($taxonKey->taxonKeyId->equals($taxonKeyId)) {
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
        if (! $taxonKey->taxonId->equals($this->taxonId)) {
            throw new InvalidTaxonIdOnTaxonKey('Cannot add or update TaxonKey. Passed TaxonKey has TaxonId [' . $taxonKey->taxonId->get() . '] that doesn\'t match with TaxonId [' . $this->taxonId->get() . '].');
        }

        foreach ($this->taxonKeys as $index => $existingTaxonKey) {
            if ($existingTaxonKey->getLocale()->equals($taxonKey->getLocale())) {
                if (! $existingTaxonKey->taxonKeyId->equals($taxonKey->taxonKeyId)) {
                    $this->taxonKeys[$index] = $taxonKey;

                    $this->recordEvent(new TaxonKeyUpdated($this->taxonId, $taxonKey->getLocale(), $existingTaxonKey->taxonKeyId, $taxonKey->taxonKeyId));
                }

                return;
            }
        }

        $this->taxonKeys[] = $taxonKey;
    }
}
