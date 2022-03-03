<?php

namespace Thinktomorrow\Trader\Application\Common;

use Thinktomorrow\Trader\Domain\Common\Locale;

trait RendersData
{
    protected function data(string $key, string $language = null, $default = null)
    {
        if(! $language && isset($this->locale) && $this->locale instanceof Locale) {
            $language = $this->locale->getLanguage();
        }

        return DataRenderer::get($this->data, $key, $language, $default);
    }
}
