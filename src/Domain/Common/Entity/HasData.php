<?php
declare(strict_types=1);

namespace Thinktomorrow\Trader\Domain\Common\Entity;

trait HasData
{
    protected array $data = [];

    public function addData(array $data): void
    {
        $this->data = array_merge($this->data, $data);
    }

    public function replaceData(array $data): void
    {
        $this->data = $data;
    }

    public function deleteData(string $key): void
    {
        if (isset($this->data[$key])) {
            unset($this->data[$key]);
        }
    }

    public function getData(?string $key = null, $default = null)
    {
        if (! is_null($key)) {
            return data_get($this->data, $key, $default);
        }

        return $this->data;
    }

    private function addDataIfNotNull(array $values): array
    {
        $data = $this->data;

        foreach ($values as $key => $value) {
            if ($value) {
                $data[$key] = $value;
            }
        }

        return $data;
    }
}
