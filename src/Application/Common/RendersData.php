<?php

namespace Thinktomorrow\Trader\Application\Common;

trait RendersData
{
    use HasLocale;

    protected function data(string $key, ?string $language = null, $default = null, ?array $data = null)
    {
        if (! $language) {
            $language = $this->getLocale()->getLanguage();
        }

        return DataRenderer::get($data ?? ($this->data ?? []), $key, $language, $default);
    }

    protected function dataAsPrimitive(string $key, ?string $language = null, $default = null, ?array $data = null)
    {
        $result = $this->data($key, $language, $default, $data);

        if (is_array($result)) {
            return $default;
        }

        return $result;
    }
}
