<?php

namespace Bermuda\Polyglot;

use Bermuda\Polyglot\Exception\InvalidLocaleException;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

/**
 * PSR-15 Middleware for setting locale from request
 */
class I18nMiddleware implements MiddlewareInterface
{
    private I18n $i18n;
    private ?string $defaultLocale;

    /**
     * @param I18n $i18n I18n service
     * @param string|null $defaultLocale Default locale to use if none detected
     */
    public function __construct(I18n $i18n, ?string $defaultLocale = null)
    {
        $this->i18n = $i18n;
        $this->defaultLocale = $defaultLocale;
    }

    /**
     * Process an incoming server request.
     *
     * @param ServerRequestInterface $request
     * @param RequestHandlerInterface $handler
     * @return ResponseInterface
     * @throws InvalidLocaleException
     */
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $this->i18n->detectAndSetLocaleFromRequest($request, $this->defaultLocale);
        return $handler->handle($request->withAttribute(Locale::class, $this->i18n->getLocale()));
    }
}



