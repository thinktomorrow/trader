<?php
declare(strict_types=1);

namespace Tests\Acceptance;

use Illuminate\Support\Arr;
use Thinktomorrow\Trader\Application\Common\DataRenderer;
use Thinktomorrow\Trader\Application\Common\DefaultLocale;
use Thinktomorrow\Trader\Domain\Common\Locale;
use Thinktomorrow\Trader\Domain\Common\Taxes\TaxRate;
use Thinktomorrow\Trader\Domain\Model\Order\Discount\DiscountPriceDefaults;

class TestCase extends \PHPUnit\Framework\TestCase
{
    protected function setUp(): void
    {
        DiscountPriceDefaults::setDiscountTaxRate(TaxRate::fromString('21'));
        DiscountPriceDefaults::setDiscountIncludeTax(true);

        DefaultLocale::set(Locale::fromString('nl'));

        DataRenderer::setDataResolver(function (array $data, string $key, string $language = null, string $default = null) {
            if (! $language) {
                $language = 'nl';
            }

            $value = Arr::get(
                $data,
                $key . '.' . $language,
                Arr::get($data, $key, $default)
            );

            return $value === null ? $default :$value;
        });
    }
}
