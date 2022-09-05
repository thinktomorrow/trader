<?php
declare(strict_types=1);

namespace Thinktomorrow\Trader\Application\Country;

use Thinktomorrow\Trader\Application\Common\RendersData;
use Thinktomorrow\Trader\Domain\Model\Country\CountryId;

class Country
{
    use RendersData;

    public readonly CountryId $countryId;

    final private function __construct()
    {
    }

    public function getLabel(): string
    {
        return $this->data('label', null, $this->countryId->get());
    }

    public static function fromMappedData(array $state): static
    {
        $country = new static();

        $country->countryId = CountryId::fromString($state['country_id']);
        $country->data = json_decode($state['data'], true);

        return $country;
    }
}
