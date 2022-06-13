<?php
declare(strict_types=1);

namespace Tests\Acceptance;

use Thinktomorrow\Trader\Domain\Common\Locale;
use Thinktomorrow\Trader\Application\Common\DataRenderer;
use Thinktomorrow\Trader\Application\Common\DefaultLocale;

class TestCase extends \PHPUnit\Framework\TestCase
{
    protected function setUp(): void
    {
        DefaultLocale::set(Locale::fromString('nl', 'BE'));

        DataRenderer::setDataResolver(function(array $data, string $key, string $language = null, string $default = null)
        {
            if(!isset($data[$key])) {
                return $default;
            }

            if(!$language) {
                $language = 'nl';
            }

            if(isset($data[$key][$language])) {
                return $data[$key][$language];
            }

            return $data[$key];
        });
    }
}
