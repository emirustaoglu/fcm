<?php

declare(strict_types=1);

namespace emirustaoglu\fcm;

use emirustaoglu\fcm\Auth\ServiceAccount;
use emirustaoglu\fcm\Exceptions\FcmRequestException;

final class Fcm
{
    private const BASE_URL = 'https://fcm.googleapis.com/v1/projects/%s/messages:send';

    public function __construct(
        private readonly ServiceAccount $credentials,
        private readonly int $timeout = 30
    ) {
    }

    public static function fromServiceAccount(string $path, int $timeout = 30): self
    {
        return new self(ServiceAccount::fromJsonFile($path), $timeout);
    }

    public function send(Message $message, bool $validateOnly = false): array
    {
        $payload = [
            'message' => $message->toArray(),
        ];

        if ($validateOnly) {
            $payload['validate_only'] = true;
        }

        return $this->request($payload);
    }

    public function sendToToken(
        string $token,
        ?string $title = null,
        ?string $body = null,
        array $data = [],
        array $webPush = [],
        ?string $image = null
    ): array {
        return $this->send(
            Message::create()
                ->token($token)
                ->notification($title, $body, $image)
                ->data($data)
                ->webPush($webPush)
        );
    }

    public function sendToTopic(
        string $topic,
        ?string $title = null,
        ?string $body = null,
        array $data = [],
        array $webPush = [],
        ?string $image = null
    ): array {
        return $this->send(
            Message::create()
                ->topic($topic)
                ->notification($title, $body, $image)
                ->data($data)
                ->webPush($webPush)
        );
    }

    public function sendToCondition(
        string $condition,
        ?string $title = null,
        ?string $body = null,
        array $data = [],
        array $webPush = [],
        ?string $image = null
    ): array {
        return $this->send(
            Message::create()
                ->condition($condition)
                ->notification($title, $body, $image)
                ->data($data)
                ->webPush($webPush)
        );
    }

    private function request(array $payload): array
    {
        $token = $this->credentials->getAccessToken();
        $url = sprintf(self::BASE_URL, rawurlencode($this->credentials->getProjectId()));

        $result = $this->curlRequest($url, $token, $payload);

        if ($result['status'] === 401) {
            $token = $this->credentials->getAccessToken(true);
            $result = $this->curlRequest($url, $token, $payload);
        }

        if ($result['status'] < 200 || $result['status'] >= 300) {
            $message = $result['json']['error']['message']
                ?? $result['json']['error']
                ?? 'FCM request failed.';

            throw new FcmRequestException(
                $message,
                $result['status'],
                $result['json']
            );
        }

        return $result['json'] ?? [];
    }

    private function curlRequest(string $url, string $accessToken, array $payload): array
    {
        $json = json_encode($payload, JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES);

        $ch = curl_init($url);
        if ($ch === false) {
            throw new FcmRequestException('Unable to initialize cURL.');
        }

        curl_setopt_array($ch, [
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => $json,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER => [
                'Authorization: Bearer ' . $accessToken,
                'Content-Type: application/json; UTF-8',
                'Accept: application/json',
            ],
            CURLOPT_CONNECTTIMEOUT => min(10, $this->timeout),
            CURLOPT_TIMEOUT => $this->timeout,
        ]);

        $body = curl_exec($ch);
        $error = curl_error($ch);
        $errno = curl_errno($ch);
        $status = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($body === false) {
            throw new FcmRequestException(
                "FCM HTTP request failed ({$errno}): {$error}"
            );
        }

        try {
            $decoded = $body === '' ? [] : json_decode($body, true, 512, JSON_THROW_ON_ERROR);
        } catch (\JsonException) {
            $decoded = ['raw' => $body];
        }

        return [
            'status' => $status,
            'json' => is_array($decoded) ? $decoded : ['data' => $decoded],
        ];
    }
}