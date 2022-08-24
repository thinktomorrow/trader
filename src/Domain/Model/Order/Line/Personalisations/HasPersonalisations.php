<?php
declare(strict_types=1);

namespace Thinktomorrow\Trader\Domain\Model\Order\Line\Personalisations;

trait HasPersonalisations
{
    private array $personalisations = [];

    public function addPersonalisation(LinePersonalisation $personalisation): void
    {
        $this->personalisations[] = $personalisation;
    }

    public function deletePersonalisation(LinePersonalisationId $personalisationId): void
    {
        /** @var LinePersonalisation $existingPersonalisation */
        foreach ($this->personalisations as $indexToBeDeleted => $existingPersonalisation) {
            if ($existingPersonalisation->linePersonalisationId->equals($personalisationId)) {
                unset($this->personalisations[$indexToBeDeleted]);
            }
        }
    }

    public function getPersonalisations(): array
    {
        return $this->personalisations;
    }

    public function deletePersonalisations(): void
    {
        $this->personalisations = [];
    }
}
