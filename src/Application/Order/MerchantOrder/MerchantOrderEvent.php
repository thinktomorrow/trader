<?php

namespace Thinktomorrow\Trader\Application\Order\MerchantOrder;

interface MerchantOrderEvent
{
    public static function fromMappedData(array $state, array $orderState): static;

    public function getOrderEventId(): string;

    public function getEvent(): string;

    public function getCreatedAt(): \DateTime;

    /**
     * Retrieve a value from the data.
     * @param string $key
     * @return mixed
     */
    public function getData(string $key, ?string $language = null, $default = null);
}
