<?php

declare(strict_types=1);

namespace Utils;

use Psr\Log\LoggerInterface;
use Psr\Log\LoggerTrait;

/**
 * Sample logger for writing into stderr.
 */
class Logger implements LoggerInterface
{
    use LoggerTrait;

    public function log($level, $message, array $context = array()): void
    {
        file_put_contents('php://stderr', sprintf('[%s] %s', $level, $message));
    }
}
