<?php
declare(strict_types=1);

namespace Thinktomorrow\Trader\Application\Product\UpdateProduct;

use Thinktomorrow\Trader\Domain\Model\Product\Personalisation\PersonalisationId;
use Thinktomorrow\Trader\Domain\Model\Product\Personalisation\PersonalisationType;

class UpdateProductPersonalisationItem
{
    private ?string $personalisationId;
    private string $personalisationType;
    private array $data;

    public function __construct(?string $personalisationId, string $personalisationType, array $data)
    {
        $this->personalisationId = $personalisationId;
        $this->personalisationType = $personalisationType;
        $this->data = $data;
    }

    public function getPersonalisationId(): ?PersonalisationId
    {
        return $this->personalisationId ? PersonalisationId::fromString($this->personalisationId) : null;
    }

    public function getPersonalisationType(): PersonalisationType
    {
        return PersonalisationType::fromString($this->personalisationType);
    }

    public function getData(): array
    {
        return $this->data;
    }
}
