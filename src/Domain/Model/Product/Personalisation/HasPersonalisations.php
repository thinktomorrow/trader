<?php
declare(strict_types=1);

namespace Thinktomorrow\Trader\Domain\Model\Product\Personalisation;

use Assert\Assertion;

trait HasPersonalisations
{
    /** @var Personalisation[] */
    private array $personalisations = [];

    /** @return Personalisation[] */
    public function getPersonalisations(): array
    {
        return $this->personalisations;
    }

    public function getNextPersonalisationId(): PersonalisationId
    {
        $i = mt_rand(1, 999);
        $nextPersonalisationId = PersonalisationId::fromString(substr($i . '_' . $this->productId->get(), 0, 36));

        while ($this->hasPersonalisation($nextPersonalisationId)) {
            $nextPersonalisationId = PersonalisationId::fromString(substr(++$i . '_' . $this->productId->get(), 0, 36));
        }

        return $nextPersonalisationId;
    }

    public function updatePersonalisations(array $personalisations): void
    {
        Assertion::allIsInstanceOf($personalisations, Personalisation::class);

        // Remove current personalisations.
        $this->personalisations = [];

        $this->personalisations = $personalisations;
    }

    private function hasPersonalisation(PersonalisationId $personalisationId): bool
    {
        /** @var Personalisation $personalisation */
        foreach ($this->personalisations as $personalisation) {
            if ($personalisation->personalisationId->equals($personalisationId)) {
                return true;
            }
        }

        return false;
    }
}
