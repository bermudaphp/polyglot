<?php

namespace Bermuda\Polyglot\Detector;

use Bermuda\Polyglot\Detector;
use Psr\Http\Message\ServerRequestInterface;

/**
 * Locale detector chain that tries multiple detectors
 */
class LocaleDetectorChain implements Detector\PsrRequestAwareLocaleDetectorInterface
{
    /** @var LocaleDetectorInterface[] */
    private array $detectors;

    /**
     * @param LocaleDetectorInterface[] $detectors
     */
    public function __construct(array $detectors = [])
    {
        $this->detectors = $detectors;
    }

    /**
     * Add a detector to the chain
     */
    public function addDetector(LocaleDetectorInterface $detector): self
    {
        $this->detectors[] = $detector;
        return $this;
    }

    /**
     * Try all detectors in order
     */
    public function detect(): ?string
    {
        foreach ($this->detectors as $detector) {
            $locale = $detector->detect();
            if ($locale !== null) {
                return $locale;
            }
        }

        return null;
    }

    /**
     * Try all detectors in order with a PSR-7 request
     */
    public function detectFromRequest(ServerRequestInterface $request): ?string
    {
        foreach ($this->detectors as $detector) {
            if ($detector instanceof Detector\PsrRequestAwareLocaleDetectorInterface) {
                $locale = $detector->detectFromRequest($request);
                if ($locale !== null) {
                    return $locale;
                }
            } else {
                $locale = $detector->detect();
                if ($locale !== null) {
                    return $locale;
                }
            }
        }

        return null;
    }
}