<?php
/**
 * Base Middleware Interface
 */

declare(strict_types=1);

interface MiddlewareInterface
{
    public function handle(): bool;
}
