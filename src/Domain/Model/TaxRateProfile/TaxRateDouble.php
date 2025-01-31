<?php
declare(strict_types=1);

namespace Thinktomorrow\Trader\Domain\Model\TaxRateProfile;

use Thinktomorrow\Trader\Domain\Common\Entity\ChildEntity;
use Thinktomorrow\Trader\Domain\Common\Taxes\TaxRate;

final class TaxRateDouble implements ChildEntity
{
    public readonly TaxRateDoubleId $taxRateDoubleId;
    public readonly TaxRateProfileId $taxRateProfileId;
    private TaxRate $originalRate;
    private TaxRate $rate;

    private function __construct()
    {
    }

    public static function create(TaxRateDoubleId $taxRateDoubleId, TaxRateProfileId $taxRateProfileId, TaxRate $originalRate, TaxRate $rate): static
    {
        $object = new static();

        $object->taxRateDoubleId = $taxRateDoubleId;
        $object->taxRateProfileId = $taxRateProfileId;
        $object->originalRate = $originalRate;
        $object->rate = $rate;

        return $object;
    }

    public function hasOriginalRate(TaxRate $rate): bool
    {
        return $this->originalRate->equals($rate);
    }

    public function getRate(): TaxRate
    {
        return $this->rate;
    }

    public function update(TaxRate $originalRate, TaxRate $rate): void
    {
        $this->originalRate = $originalRate;
        $this->rate = $rate;
    }

    public function getMappedData(): array
    {
        return [
            'taxrate_double_id' => $this->taxRateDoubleId->get(),
            'taxrate_profile_id' => $this->taxRateProfileId->get(),
            'original_rate' => $this->originalRate->get(),
            'rate' => $this->rate->get(),
        ];
    }

    public static function fromMappedData(array $state, array $aggregateState): static
    {
        $object = new static();

        $object->taxRateDoubleId = TaxRateDoubleId::fromString($state['taxrate_double_id']);
        $object->taxRateProfileId = TaxRateProfileId::fromString($aggregateState['taxrate_profile_id']);
        $object->originalRate = TaxRate::fromString($state['original_rate']);
        $object->rate = TaxRate::fromString($state['rate']);

        return $object;
    }

    public function equals($other): bool
    {
        return get_class($other) === get_class($this)
            && $this->taxRateDoubleId === $other->taxRateDoubleId;
    }
}
