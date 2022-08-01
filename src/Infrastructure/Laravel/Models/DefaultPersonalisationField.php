<?php
declare(strict_types=1);

namespace Thinktomorrow\Trader\Infrastructure\Laravel\Models;

use Thinktomorrow\Trader\Application\Common\HasLocale;
use Thinktomorrow\Trader\Application\Common\RendersData;
use Thinktomorrow\Trader\Application\Product\Personalisations\PersonalisationField;
use Thinktomorrow\Trader\Domain\Model\Product\Personalisation\Personalisation;

class DefaultPersonalisationField implements PersonalisationField
{
    use HasLocale;
    use RendersData;

    protected Personalisation $personalisation;

    private function __construct(Personalisation $personalisation)
    {
        $this->personalisation = $personalisation;
    }

    public static function from(Personalisation $personalisation): static
    {
        return new static($personalisation);
    }

    public function getPersonalisationId(): string
    {
        return $this->personalisation->personalisationId->get();
    }

    public function getPersonalisationType(): string
    {
        return $this->personalisation->personalisationType->get();
    }

    public function getLabel(): string
    {
        return $this->data('label', null, '', $this->personalisation->getData());
    }
}
