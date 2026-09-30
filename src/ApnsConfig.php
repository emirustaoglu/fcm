<?php

declare(strict_types=1);

namespace emirustaoglu\fcm;

final class ApnsConfig
{
    public function __construct(
        private readonly array $headers = [],
        private readonly array $payload = []
    ) {
    }

    public function toArray(): array
    {
        $apns = [];

        if ($this->headers !== []) {
            $apns['headers'] = $this->headers;
        }

        if ($this->payload !== []) {
            $apns['payload'] = $this->payload;
        }

        return $apns;
    }
}