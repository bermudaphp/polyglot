<?php

namespace Bermuda\Polyglot\Detector;

use Bermuda\Polyglot\Detector;
use Psr\Http\Message\ServerRequestInterface;

/**
 * Interface for PSR-7 request-aware locale detector
 */
interface PsrRequestAwareLocaleDetectorInterface extends Detector\LocaleDetectorInterface
{
    /**
     * Detect the preferred locale from a PSR-7 request
     */
    public function detectFromRequest(ServerRequestInterface $request): ?string;
}