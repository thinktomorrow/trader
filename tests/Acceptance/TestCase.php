<?php
declare(strict_types=1);

namespace Tests\Acceptance;

use Thinktomorrow\Trader\Domain\Common\Taxes\TaxRate;
use Thinktomorrow\Trader\Application\Common\DataRenderer;
use Thinktomorrow\Trader\Application\Common\DefaultLocale;
use Thinktomorrow\Trader\Domain\Common\Locale;
use Thinktomorrow\Trader\Domain\Model\Order\Discount\DiscountTotal;

class TestCase extends \PHPUnit\Framework\TestCase
{
    protected function setUp(): void
    {
        DiscountTotal::setDiscountTaxRate(TaxRate::fromString('21'));

        DefaultLocale::set(Locale::fromString('nl', 'BE'));

        DataRenderer::setDataResolver(function (array $data, string $key, string $language = null, string $default = null) {
            if (! isset($data[$key])) {
                return $default;
            }

            if (! $language) {
                $language = 'nl';
            }

            if (isset($data[$key][$language])) {
                return $data[$key][$language];
            }

            return $data[$key];
        });
    }
}
