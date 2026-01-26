<?php

declare(strict_types=1);

namespace App\Exceptions;

use Exception;

/**
 * RateLimitException - API Rate Limit Xatosi
 *
 * Bu exception Instagram/Facebook API rate limit bo'lganda ishlatiladi.
 * Job buni ushlab, keyinroqqa qoldiriladi.
 */
class RateLimitException extends Exception
{
    /**
     * Qayta urinish uchun kutish vaqti (soniya)
     */
    protected int $retryAfter;

    public function __construct(string $message = 'Rate limit exceeded', int $code = 429, ?int $retryAfter = null)
    {
        parent::__construct($message, $code);
        $this->retryAfter = $retryAfter ?? 60;
    }

    /**
     * Qayta urinish uchun kutish vaqti
     */
    public function getRetryAfter(): int
    {
        return $this->retryAfter;
    }
}
