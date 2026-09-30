<?php

declare(strict_types=1);

namespace emirustaoglu\fcm\Exceptions;

class FcmRequestException extends FcmException
{
    public function __construct(
        string $message,
        private readonly int $statusCode = 0,
        private readonly ?array $response = null,
        ?\Throwable $previous = null
    ) {
        parent::__construct($message, $statusCode, $previous);
    }

    public function getStatusCode(): int
    {
        return $this->statusCode;
    }

    public function getResponse(): ?array
    {
        return $this->response;
    }
}