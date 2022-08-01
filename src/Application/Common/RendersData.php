<?php

namespace Thinktomorrow\Trader\Application\Common;

trait RendersData
{
    use HasLocale;

    protected function data(string $key, string $language = null, $default = null, array $data = null)
    {
        if (! $language) {
            $language = $this->getLocale()->getLanguage();
        }

        return DataRenderer::get($data ?? ($this->data ?? []), $key, $language, $default);
    }
}
