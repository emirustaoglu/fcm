<?php

declare(strict_types=1);

namespace emirustaoglu\fcm;

final class AndroidConfig
{
    private array $data = [];

    public function __construct(
        private readonly ?string $priority = null,
        private readonly ?string $ttl = null,
        private readonly ?string $collapseKey = null,
        private readonly ?string $channelId = null
    ) {
    }

    public function toArray(): array
    {
        $notification = array_filter([
            'channel_id' => $this->channelId,
        ], static fn ($value) => $value !== null);

        return array_filter([
            'priority' => $this->priority,
            'ttl' => $this->ttl,
            'collapse_key' => $this->collapseKey,
            'notification' => $notification ?: null,
        ], static fn ($value) => $value !== null);
    }
}