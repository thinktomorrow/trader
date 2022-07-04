<?php
declare(strict_types=1);

namespace Thinktomorrow\Trader\Domain\Model\Country;

use Thinktomorrow\Trader\Domain\Common\Entity\Entity;
use Thinktomorrow\Trader\Domain\Common\Entity\HasData;
use Thinktomorrow\Trader\Domain\Common\Event\RecordsEvents;

class Country implements Entity
{
    use HasData;
    use RecordsEvents;

    public readonly CountryId $countryId;

    private function __construct()
    {
    }

    public static function create(CountryId $countryId, array $data): static
    {
        $country = new static();
        $country->countryId = $countryId;
        $country->data = $data;

        return $country;
    }

    public function getMappedData(): array
    {
        return [
            'country_id' => $this->countryId->get(),
            'data' => json_encode($this->data),
        ];
    }

    public static function fromMappedData(array $state): static
    {
        $country = new static();
        $country->countryId = CountryId::fromString($state['country_id']);
        $country->data = json_decode($state['data'], true);

        return $country;
    }
}
