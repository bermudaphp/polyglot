<?php

namespace Bermuda\Polyglot\Detector;

/**
 * Interface for locale detector strategies
 */
interface LocaleDetectorInterface
{
    /**
     * Detect the preferred locale
     */
    public function detect(): ?string;
}