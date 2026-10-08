<?php

namespace App\Support\StartEntreprise;

use RuntimeException;

/**
 * No access token could be obtained. The error code is safe to store and log: it never contains
 * the client secret or a token.
 */
class StartEntrepriseTokenException extends RuntimeException
{
    public function __construct(public readonly string $errorCode, public readonly bool $retryable)
    {
        parent::__construct("StartEntreprise token request failed: {$errorCode}");
    }
}
