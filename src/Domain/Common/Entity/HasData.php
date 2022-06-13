<?php
declare(strict_types=1);

namespace Thinktomorrow\Trader\Domain\Common\Entity;

trait HasData
{
    private array $data = [];

    public function addData(array $data): void
    {
        $this->data = array_merge($this->data, $data);
    }

    public function deleteData(string $key): void
    {
        if (isset($this->data[$key])) {
            unset($this->data[$key]);
        }
    }

    public function getData(string $key = null)
    {
        if (! is_null($key)) {
            return data_get($this->data, $key);
        }

        return $this->data;
    }
}
