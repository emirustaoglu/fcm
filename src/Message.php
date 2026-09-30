<?php

declare(strict_types=1);

namespace emirustaoglu\fcm;

use InvalidArgumentException;

final class Message
{
    private ?string $token = null;
    private ?string $topic = null;
    private ?string $condition = null;
    private ?Notification $notification = null;
    private array $data = [];
    private array $webPush = [];
    private ?AndroidConfig $android = null;
    private ?ApnsConfig $apns = null;

    public static function create(): self
    {
        return new self();
    }

    public function token(string $token): self
    {
        $this->token = trim($token);
        $this->topic = null;
        $this->condition = null;

        return $this;
    }

    public function topic(string $topic): self
    {
        $this->topic = trim($topic);
        $this->token = null;
        $this->condition = null;

        return $this;
    }

    public function condition(string $condition): self
    {
        $this->condition = trim($condition);
        $this->token = null;
        $this->topic = null;

        return $this;
    }

    public function notification(?string $title = null, ?string $body = null, ?string $image = null): self
    {
        $this->notification = new Notification($title, $body, $image);

        return $this;
    }

    public function data(array $data): self
    {
        foreach ($data as $key => $value) {
            if (!is_string($key) && !is_int($key)) {
                throw new InvalidArgumentException('FCM data keys must be strings or integers.');
            }

            if (!is_scalar($value) && $value !== null) {
                throw new InvalidArgumentException(
                    "FCM data value for '{$key}' must be scalar or null."
                );
            }

            $data[$key] = $value === null ? '' : (string) $value;
        }

        $this->data = $data;

        return $this;
    }

    public function webPush(array $webPush): self
    {
        if (isset($webPush['notification'])) {
            if (!is_array($webPush['notification'])) {
                throw new InvalidArgumentException(
                    'FCM webpush notification must be an array.'
                );
            }

            if (
                isset($webPush['notification']['requireInteraction'])
                && !is_bool($webPush['notification']['requireInteraction'])
            ) {
                throw new InvalidArgumentException(
                    'FCM webpush notification requireInteraction must be boolean.'
                );
            }

            if (
                isset($webPush['notification']['icon'])
                && !is_string($webPush['notification']['icon'])
            ) {
                throw new InvalidArgumentException(
                    'FCM webpush notification icon must be a string.'
                );
            }
        }

        if (isset($webPush['fcm_options'])) {
            if (!is_array($webPush['fcm_options'])) {
                throw new InvalidArgumentException(
                    'FCM webpush fcm_options must be an array.'
                );
            }

            if (
                isset($webPush['fcm_options']['link'])
                && !is_string($webPush['fcm_options']['link'])
            ) {
                throw new InvalidArgumentException(
                    'FCM webpush fcm_options link must be a string.'
                );
            }
        }

        $this->webPush = $webPush;

        return $this;
    }

    public function android(AndroidConfig $android): self
    {
        $this->android = $android;

        return $this;
    }

    public function apns(ApnsConfig $apns): self
    {
        $this->apns = $apns;

        return $this;
    }

    public function toArray(): array
    {
        $targetCount = (int) ($this->token !== null) +
            (int) ($this->topic !== null) +
            (int) ($this->condition !== null);

        if ($targetCount !== 1) {
            throw new InvalidArgumentException('Exactly one FCM target is required: token, topic or condition.');
        }

        $message = [];

        if ($this->token !== null) {
            $message['token'] = $this->token;
        } elseif ($this->topic !== null) {
            $message['topic'] = $this->topic;
        } else {
            $message['condition'] = $this->condition;
        }

        if ($this->notification !== null) {
            $message['notification'] = $this->notification->toArray();
        }

        if ($this->data !== []) {
            $message['data'] = $this->data;
        }

        if ($this->webPush !== []) {
            $message['webpush'] = $this->webPush;
        }

        if ($this->android !== null) {
            $message['android'] = $this->android->toArray();
        }

        if ($this->apns !== null) {
            $message['apns'] = $this->apns->toArray();
        }

        return $message;
    }
}
