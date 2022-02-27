<?php
declare(strict_types=1);

namespace Tests\Infrastructure\Common;

use Thinktomorrow\Trader\Application\Common\RendersData;

class RenderDataStub
{
    use RendersData;

    private array $data;

    public function __construct(array $data)
    {
        $this->data = $data;
    }

    public function get(string $key, string $language = null, string $default = null)
    {
        return $this->data($key, $language, $default);
    }
}
