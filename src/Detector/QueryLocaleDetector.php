<?php

namespace Bermuda\Polyglot\Detector;

use Psr\Http\Message\ServerRequestInterface;

/**
 * PSR-7 Request query parameter locale detector
 * Extracts locale from query parameter, e.g., ?locale=en
 */
class QueryLocaleDetector implements PsrRequestAwareLocaleDetectorInterface
{
    /** @var string[] */
    private array $availableLocales;

    /** @var string */
    private string $paramName;

    /**
     * @param string[] $availableLocales List of available locales
     * @param string $paramName Query parameter name for locale
     */
    public function __construct(array $availableLocales, string $paramName = 'locale')
    {
        $this->availableLocales = $availableLocales;
        $this->paramName = $paramName;
    }

    /**
     * Detect locale from query parameter using global $_GET
     */
    public function detect(): ?string
    {
        if (!isset($_GET[$this->paramName])) {
            return null;
        }

        $locale = $_GET[$this->paramName];

        if (in_array($locale, $this->availableLocales, true)) {
            return $locale;
        }

        return null;
    }

    /**
     * Detect locale from PSR-7 request
     */
    public function detectFromRequest(ServerRequestInterface $request): ?string
    {
        $params = $request->getQueryParams();

        if (!isset($params[$this->paramName])) {
            return null;
        }

        $locale = $params[$this->paramName];

        if (in_array($locale, $this->availableLocales, true)) {
            return $locale;
        }

        return null;
    }
}