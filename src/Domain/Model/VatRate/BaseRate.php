<?php
declare(strict_types=1);

namespace Thinktomorrow\Trader\Domain\Model\VatRate;

use Thinktomorrow\Trader\Domain\Common\Entity\ChildEntity;
use Thinktomorrow\Trader\Domain\Common\Vat\VatPercentage;

final class BaseRate implements ChildEntity
{
    public readonly BaseRateId $baseRateId;
    public readonly VatRateId $originVatRateId;
    public readonly VatRateId $targetVatRateId;
    public readonly VatPercentage $rate;

    public static function create(BaseRateId $baseRateId, VatRateId $originVatRateId, VatRateId $targetVatRateId, VatPercentage $rate): static
    {
        $object = new static();
        $object->baseRateId = $baseRateId;
        $object->originVatRateId = $originVatRateId;
        $object->targetVatRateId = $targetVatRateId;
        $object->rate = $rate;

        return $object;
    }

    public function getMappedData(): array
    {
        return [
            'base_rate_id' => $this->baseRateId->get(),
            'origin_vat_rate_id' => $this->originVatRateId->get(),
            'target_vat_rate_id' => $this->targetVatRateId->get(),
        ];
    }

    public static function fromMappedData(array $state, array $aggregateState): static
    {
        $object = new static();
        $object->baseRateId = BaseRateId::fromString($state['base_rate_id']);
        $object->originVatRateId = VatRateId::fromString($state['origin_vat_rate_id']);
        $object->targetVatRateId = VatRateId::fromString($aggregateState['vat_rate_id']);
        $object->rate = VatPercentage::fromString($state['rate']);

        return $object;
    }
}
