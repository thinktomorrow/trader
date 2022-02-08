<?php
declare(strict_types=1);

namespace Thinktomorrow\Trader\Common\Domain;

class Context
{
    private ChannelId $channel;
    private Locale $locale;

    private static ?Context $current = null;

    public function __construct(ChannelId $channel, Locale $locale)
    {
        $this->channel = $channel;
        $this->locale = $locale;
    }

    public function getChannel(): ChannelId
    {
        return $this->channel;
    }

    public function getLocale(): Locale
    {
        return $this->locale;
    }

    public static function current(): ?self
    {
        return static::$current;
    }

    public static function setCurrent(Context $context): void
    {
        static::$current = $context;
    }
}
