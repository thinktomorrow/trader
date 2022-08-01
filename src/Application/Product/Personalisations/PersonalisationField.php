<?php
declare(strict_types=1);

namespace Thinktomorrow\Trader\Application\Product\Personalisations;

use Thinktomorrow\Trader\Domain\Model\Product\Personalisation\Personalisation;

interface PersonalisationField
{
    public static function from(Personalisation $personalisation): static;

    public function getPersonalisationId(): string;

    public function getPersonalisationType(): string;

    public function getLabel(): string;
}
