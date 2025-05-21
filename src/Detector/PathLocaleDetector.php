<?php

namespace Bermuda\Polyglot\Detector;

use Psr\Http\Message\ServerRequestInterface;

/**
 * PSR-7 Request path locale detector
 * Extracts locale from the URL path, e.g., /en/page, /fr/page, etc.
 */
class PathLocaleDetector implements PsrRequestAwareLocaleDetectorInterface
{
    /** @var string[] */
    private array $availableLocales;

    /** @var string */
    private string $pathPrefix;

    /**
     * @param string[] $availableLocales List of available locales
     * @param string $pathPrefix Path prefix before the locale segment
     */
    public function __construct(array $availableLocales, string $pathPrefix = '')
    {
        $this->availableLocales = $availableLocales;
        $this->pathPrefix = rtrim($pathPrefix, '/');
    }

    /**
     * Detect locale from URL path using global $_SERVER
     */
    public function detect(): ?string
    {
        if (!isset($_SERVER['REQUEST_URI'])) {
            return null;
        }

        $path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH) ?? '';
        return $this->detectFromPath($path);
    }

    /**
     * Detect locale from PSR-7 request
     */
    public function detectFromRequest(ServerRequestInterface $request): ?string
    {
        $path = $request->getUri()->getPath();
        return $this->detectFromPath($path);
    }

    /**
     * Extract locale from URL path
     */
    private function detectFromPath(string $path): ?string
    {
        if (!empty($this->pathPrefix) && str_starts_with($path, $this->pathPrefix)) {
            $path = substr($path, strlen($this->pathPrefix));
        }

        $segments = array_filter(explode('/', $path));
        $possibleLocale = reset($segments);

        if ($possibleLocale && in_array($possibleLocale, $this->availableLocales, true)) {
            return $possibleLocale;
        }

        return null;
    }
}