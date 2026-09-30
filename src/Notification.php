<?php

declare(strict_types=1);

namespace emirustaoglu\fcm;

final class Notification
{
    public function __construct(
        private readonly ?string $title = null,
        private readonly ?string $body = null,
        private readonly ?string $image = null
    ) {
    }

    public function toArray(): array
    {
        return array_filter([
            'title' => $this->title,
            'body' => $this->body,
            'image' => $this->image,
        ], static fn ($value) => $value !== null);
    }
}