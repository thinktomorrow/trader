<?php
declare(strict_types=1);

namespace Thinktomorrow\Trader\Domain\Common\Cash;

class IntegerConverter
{
    /**
     * Convert float or decimal to integer value
     * e.g. used to inject into our Money object
     *
     * @param string $float
     * @return int
     */
    public static function convertDecimalToInteger(string $float, string $decimal_separator = ',', string $thousand_separator = '.'): int
    {
        // Cope with decimals who use a comma as decimal separator
        if (strpos($float, $decimal_separator)) {
            // Remove possible . as thousand separator first to avoid being interpreted as decimal separator
            $float = str_replace([$thousand_separator, $decimal_separator], ['', '.'], $float);
        }

        return (int) number_format((float) $float, 2, '', '');
    }

    /**
     * Convert an integer to its decimal counterpart.
     * Used to display amounts in user interfaces.
     *
     * @param int $amount
     * @param int $decimals
     * @return string
     */
    public static function convertIntegerToDecimal(int $amount, int $decimals = 2, string $decimal_separator = ',', string $thousand_separator = '.'): string
    {
        $amount = $amount / (10 ** $decimals);
        $amount = floatval($amount);

        return number_format($amount, $decimals, $decimal_separator, $thousand_separator);
    }
}
