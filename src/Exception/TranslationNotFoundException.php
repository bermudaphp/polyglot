<?php

namespace Bermuda\Polyglot\Exception;

/**
 * Exception thrown when a translation is not found
 */
class TranslationNotFoundException extends I18nException
{
    public function __construct(
        string      $key,
        ?string     $locale = null,
        ?string     $domain = null,
        ?\Throwable $previous = null
    )
    {
        $message = "Translation not found for key '$key'";
        if ($locale !== null) {
            $message .= " in locale '$locale'";
        }
        if ($domain !== null) {
            $message .= " and domain '$domain'";
        }
        parent::__construct($message, 0, $previous);
    }
}