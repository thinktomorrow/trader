<?php
declare(strict_types=1);

namespace Tests\Unit\Common;

use Tests\Unit\TestCase;
use Thinktomorrow\Trader\Domain\Common\Locale;

final class LocaleTest extends TestCase
{
    public function test_it_can_create_locale()
    {
        $locale = Locale::fromString('nl_BE');

        $this->assertEquals('nl', $locale->getLanguage());
        $this->assertEquals('be', $locale->getRegion());
        $this->assertEquals('nl_BE', $locale->toIso15897());
        $this->assertEquals('nl-BE', $locale->toIso639());
    }

    public function test_it_can_create_locale_from_only_language()
    {
        $locale = Locale::fromString('nl');

        $this->assertEquals('nl', $locale->getLanguage());
        $this->assertEquals('nl', $locale->getRegion());
        $this->assertEquals('nl_NL', $locale->toIso15897());
        $this->assertEquals('nl', $locale->toIso639());
    }

    public function test_it_can_create_locale_from_Iso639_string()
    {
        $locale = Locale::fromString('nl-be');

        $this->assertEquals('nl', $locale->getLanguage());
        $this->assertEquals('be', $locale->getRegion());
        $this->assertEquals('nl_BE', $locale->toIso15897());
        $this->assertEquals('nl-BE', $locale->toIso639());
    }

    public function test_it_can_check_if_locale_is_equals()
    {
        $this->assertTrue(Locale::fromString('nl-be')->equals(Locale::fromString('nl-be')));
        $this->assertTrue(Locale::fromString('nl-BE')->equals(Locale::fromString('nl-be')));
        $this->assertTrue(Locale::fromString('nl_BE')->equals(Locale::fromString('nl-be')));

        $this->assertFalse(Locale::fromString('nl-nl')->equals(Locale::fromString('nl-be')));
    }

    public function test_it_cannot_create_locale_from_invalid_input()
    {
        $this->expectException(\InvalidArgumentException::class);
        Locale::fromString('n_be');
    }

    public function test_it_cannot_create_locale_from_invalid_construct_input()
    {
        $this->expectException(\InvalidArgumentException::class);
        Locale::fromString('');
    }
}
