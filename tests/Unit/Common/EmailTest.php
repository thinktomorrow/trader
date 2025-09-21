<?php
declare(strict_types=1);

namespace Tests\Unit\Common;

use PHPUnit\Framework\TestCase;
use Thinktomorrow\Trader\Domain\Common\Email;

class EmailTest extends TestCase
{
    public function test_it_can_create_an_email_from_string()
    {
        $email = Email::fromString('ben@example.com');

        $this->assertEquals('ben@example.com', $email->get());
    }

    public function test_it_can_check_if_string_is_valid_email()
    {
        $this->expectException(\InvalidArgumentException::class);

        Email::fromString('xxx');
    }

    public function test_it_can_compare_email_values_for_equality()
    {
        $email = Email::fromString('ben@example.com');

        $this->assertTrue($email->equals(Email::fromString('ben@example.com')));
        $this->assertFalse($email->equals(Email::fromString('john@example.com')));
        $this->assertFalse($email->equals(new \stdClass()));
    }
}
