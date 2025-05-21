<?php

namespace Bermuda\Polyglot\Formatter;

/**
 * Interface for message formatting
 */
interface MessageFormatterInterface
{
    /**
     * Format a message with parameters
     */
    public function format(string $message, array $parameters): string;
}