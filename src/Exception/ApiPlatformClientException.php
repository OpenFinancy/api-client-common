<?php

declare(strict_types=1);

namespace OpenFinancy\ApiClient\Common\Exception;

use RuntimeException;
use Throwable;

/**
 * Domain specific exception for API Platform client errors.
 */
final class ApiPlatformClientException extends RuntimeException
{
    public function __construct(
        string $message,
        private readonly ?int $statusCode = null,
        ?Throwable $previous = null,
    ) {
        parent::__construct($message, 0, $previous);
    }

    public function getStatusCode(): ?int
    {
        return $this->statusCode;
    }
}


