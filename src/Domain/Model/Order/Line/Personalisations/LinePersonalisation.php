<?php
declare(strict_types=1);

namespace Thinktomorrow\Trader\Domain\Model\Order\Line\Personalisations;

use Thinktomorrow\Trader\Domain\Common\Entity\ChildEntity;
use Thinktomorrow\Trader\Domain\Common\Entity\HasData;
use Thinktomorrow\Trader\Domain\Model\Order\Line\LineId;
use Thinktomorrow\Trader\Domain\Model\Product\Personalisation\PersonalisationId;
use Thinktomorrow\Trader\Domain\Model\Product\Personalisation\PersonalisationId as OriginalPersonalisationId;
use Thinktomorrow\Trader\Domain\Model\Product\Personalisation\PersonalisationType;

final class LinePersonalisation implements ChildEntity
{
    use HasData;

    public readonly LineId $lineId;
    public readonly LinePersonalisationId $linePersonalisationId;
    public readonly ?OriginalPersonalisationId $originalPersonalisationId;
    private PersonalisationType $personalisationType;
    private $value;

    public static function create(LineId $lineId, LinePersonalisationId $linePersonalisationId, ?OriginalPersonalisationId $originalPersonalisationId, PersonalisationType $personalisationType, $value, array $data): static
    {
        $personalisation = new static();

        $personalisation->lineId = $lineId;
        $personalisation->linePersonalisationId = $linePersonalisationId;
        $personalisation->originalPersonalisationId = $originalPersonalisationId;
        $personalisation->personalisationType = $personalisationType;
        $personalisation->value = $value;
        $personalisation->data = $data;

        return $personalisation;
    }

    public function getMappedData(): array
    {
        return [
            'line_id' => $this->lineId->get(),
            'line_personalisation_id' => $this->linePersonalisationId->get(),
            'personalisation_type' => $this->personalisationType->get(),
            'personalisation_id' => $this->originalPersonalisationId?->get(),
            'value' => $this->value,
            'data' => json_encode($this->data),
        ];
    }

    public static function fromMappedData(array $state, array $aggregateState): static
    {
        $personalisation = new static();

        $personalisation->lineId = LineId::fromString($aggregateState['line_id']);
        $personalisation->linePersonalisationId = LinePersonalisationId::fromString($state['line_personalisation_id']);
        $personalisation->originalPersonalisationId = $state['personalisation_id'] ? PersonalisationId::fromString($state['personalisation_id']) : null;
        $personalisation->personalisationType = PersonalisationType::fromString($state['personalisation_type']);
        $personalisation->value = $state['value'];
        $personalisation->data = json_decode($state['data'], true);

        return $personalisation;
    }

    public function getType(): PersonalisationType
    {
        return $this->personalisationType;
    }

    public function getValue()
    {
        return $this->value;
    }
}
