<?php

namespace Thinktomorrow\Trader\Application\Common;

trait RendersData
{
    use HasLocale;

    protected function data(string $key, string $language = null, $default = null)
    {
        if(! $language) {
            $language = $this->getLocale()->getLanguage();
        }

        return DataRenderer::get($this->data, $key, $language, $default);
    }
}
