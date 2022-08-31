<?php
declare(strict_types=1);

namespace Tests\Unit\Model\Order;

use Tests\Unit\TestCase;
use Thinktomorrow\Trader\Domain\Model\Order\Log\LogEntry;
use Thinktomorrow\Trader\Domain\Model\Order\Log\LogEntryId;

class OrderLogEntryTest extends TestCase
{
    public function test_it_can_create_entry()
    {
        $entry = LogEntry::create($logEntryId = LogEntryId::fromString('abc'), 'xxx', $createdAt = new \DateTime(), ['foo' => 'bar']);

        $this->assertEquals($logEntryId, $entry->entryId);
        $this->assertEquals('xxx', $entry->getEvent());
        $this->assertEquals($createdAt, $entry->getCreatedAt());
        $this->assertEquals(['foo' => 'bar'], $entry->getData());
    }

    public function test_it_can_be_build_from_mapped_data()
    {
        $entry = LogEntry::fromMappedData(['entry_id' => 'abc', 'event' => 'xxx', 'at' => '2022-02-02 19:19:19', 'data' => json_encode(['foo' => 'bar'])], []);

        $this->assertEquals('abc', $entry->entryId);
        $this->assertEquals('xxx', $entry->getEvent());
        $this->assertEquals(new \DateTime('2022-02-02 19:19:19'), $entry->getCreatedAt());
        $this->assertEquals(['foo' => 'bar'], $entry->getData());

        $this->assertEquals(['entry_id' => 'abc', 'event' => 'xxx', 'at' => '2022-02-02 19:19:19', 'data' => json_encode(['foo' => 'bar'])], $entry->getMappedData());
    }
}
