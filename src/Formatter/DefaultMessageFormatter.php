<?php

namespace Bermuda\Polyglot\Formatter;


/**
 * Default message formatter implementation
 */
class DefaultMessageFormatter implements MessageFormatterInterface
{
    /**
     * Format a message by replacing parameters in curly braces
     * Supports both named parameters {name} and positional parameters {0}
     */
    public function format(string $message, array $parameters): string
    {
        // Replace named parameters first
        $namedReplacements = [];
        foreach ($parameters as $key => $value) {
            if (is_scalar($value) || $value === null) {
                $namedReplacements['{' . $key . '}'] = (string)$value;
            }
        }

        $result = strtr($message, $namedReplacements);

        // Replace positional parameters (e.g., {0}, {1})
        return preg_replace_callback('/{(\d+)}/', function ($matches) use ($parameters) {
            $index = (int)$matches[1];
            return isset($parameters[$index]) && (is_scalar($parameters[$index]) || $parameters[$index] === null)
                ? (string)$parameters[$index]
                : $matches[0];
        }, $result);
    }
}