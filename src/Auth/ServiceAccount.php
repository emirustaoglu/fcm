<?php

declare(strict_types=1);

namespace emirustaoglu\fcm\Auth;

use emirustaoglu\fcm\Exceptions\AuthenticationException;

final class ServiceAccount
{
    public const SCOPE = 'https://www.googleapis.com/auth/firebase.messaging';
    private const TOKEN_URI = 'https://oauth2.googleapis.com/token';

    private ?string $accessToken = null;
    private int $accessTokenExpiresAt = 0;

    public function __construct(
        private readonly string $clientEmail,
        private readonly string $privateKey,
        private readonly string $projectId
    ) {
        if ($clientEmail === '' || $privateKey === '' || $projectId === '') {
            throw new AuthenticationException('client_email, private_key and project_id are required.');
        }
    }

    public static function fromJsonFile(string $path): self
    {
        if (!is_file($path) || !is_readable($path)) {
            throw new AuthenticationException("Service account file cannot be read: {$path}");
        }

        $json = file_get_contents($path);
        if ($json === false) {
            throw new AuthenticationException("Unable to read service account file: {$path}");
        }

        return self::fromJson($json);
    }

    public static function fromJson(string $json): self
    {
        try {
            $data = json_decode($json, true, 512, JSON_THROW_ON_ERROR);
        } catch (\JsonException $e) {
            throw new AuthenticationException('Invalid service account JSON: ' . $e->getMessage(), 0, $e);
        }

        return new self(
            (string) ($data['client_email'] ?? ''),
            (string) ($data['private_key'] ?? ''),
            (string) ($data['project_id'] ?? '')
        );
    }

    public function getProjectId(): string
    {
        return $this->projectId;
    }

    public function getAccessToken(bool $forceRefresh = false): string
    {
        if (!$forceRefresh && $this->accessToken !== null && time() < $this->accessTokenExpiresAt - 60) {
            return $this->accessToken;
        }

        $now = time();
        $claims = [
            'iss' => $this->clientEmail,
            'scope' => self::SCOPE,
            'aud' => self::TOKEN_URI,
            'iat' => $now,
            'exp' => $now + 3600,
        ];

        $assertion = $this->createJwt($claims);

        $ch = curl_init(self::TOKEN_URI);
        if ($ch === false) {
            throw new AuthenticationException('Unable to initialize cURL.');
        }

        curl_setopt_array($ch, [
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => http_build_query([
                'grant_type' => 'urn:ietf:params:oauth:grant-type:jwt-bearer',
                'assertion' => $assertion,
            ]),
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER => [
                'Content-Type: application/x-www-form-urlencoded',
                'Accept: application/json',
            ],
            CURLOPT_CONNECTTIMEOUT => 10,
            CURLOPT_TIMEOUT => 30,
        ]);

        $body = curl_exec($ch);
        $error = curl_error($ch);
        $errno = curl_errno($ch);
        $status = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($body === false) {
            throw new AuthenticationException(
                "OAuth token request failed ({$errno}): {$error}"
            );
        }

        try {
            $data = json_decode($body, true, 512, JSON_THROW_ON_ERROR);
        } catch (\JsonException $e) {
            throw new AuthenticationException(
                "OAuth token endpoint returned invalid JSON (HTTP {$status}).",
                0,
                $e
            );
        }

        if ($status < 200 || $status >= 300 || empty($data['access_token'])) {
            $message = $data['error_description'] ?? $data['error'] ?? 'Unable to obtain access token.';
            throw new AuthenticationException("OAuth authentication failed (HTTP {$status}): {$message}");
        }

        $this->accessToken = (string) $data['access_token'];
        $expiresIn = max(60, (int) ($data['expires_in'] ?? 3600));
        $this->accessTokenExpiresAt = time() + $expiresIn;

        return $this->accessToken;
    }

    private function createJwt(array $claims): string
    {
        $header = ['alg' => 'RS256', 'typ' => 'JWT'];

        $encodedHeader = self::base64UrlEncode(
            json_encode($header, JSON_THROW_ON_ERROR)
        );
        $encodedClaims = self::base64UrlEncode(
            json_encode($claims, JSON_THROW_ON_ERROR)
        );

        $signingInput = $encodedHeader . '.' . $encodedClaims;

        $privateKey = openssl_pkey_get_private($this->privateKey);
        if ($privateKey === false) {
            throw new AuthenticationException('Invalid service account private key.');
        }

        $signature = '';
        if (!openssl_sign($signingInput, $signature, $privateKey, OPENSSL_ALGO_SHA256)) {
            throw new AuthenticationException('Unable to sign OAuth JWT.');
        }

        return $signingInput . '.' . self::base64UrlEncode($signature);
    }

    private static function base64UrlEncode(string $value): string
    {
        return rtrim(strtr(base64_encode($value), '+/', '-_'), '=');
    }
}