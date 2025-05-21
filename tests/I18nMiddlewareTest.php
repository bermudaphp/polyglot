<?php

namespace Bermuda\Polyglot\Tests;

use Bermuda\Polyglot\Exception\InvalidLocaleException;
use Bermuda\Polyglot\I18n;
use Bermuda\Polyglot\I18nMiddleware;
use Bermuda\Polyglot\Locale;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\MockObject\Exception;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

#[CoversClass(I18nMiddleware::class)]
class I18nMiddlewareTest extends TestCase
{
    private I18nMiddleware $middleware;
    private MockObject $i18n;
    private MockObject $request;
    private MockObject $handler;
    private MockObject $response;

    /**
     * @throws Exception
     */
    protected function setUp(): void
    {
        $this->i18n = $this->createMock(I18n::class);
        $this->request = $this->createMock(ServerRequestInterface::class);
        $this->handler = $this->createMock(RequestHandlerInterface::class);
        $this->response = $this->createMock(ResponseInterface::class);

        $this->middleware = new I18nMiddleware($this->i18n, 'en');
    }

    /**
     * @throws InvalidLocaleException
     * @throws Exception
     */
    #[Test]
    public function processDetectsLocaleAndSetsRequestAttribute(): void
    {
        $locale = new Locale('en');
        $this->i18n->expects($this->once())
            ->method('detectAndSetLocaleFromRequest')
            ->with($this->request, 'en')
            ->willReturn($this->i18n);

        $this->i18n->expects($this->once())
            ->method('getLocale')
            ->willReturn($locale);

        $requestWithLocale = $this->createMock(ServerRequestInterface::class);

        $this->request->expects($this->once())
            ->method('withAttribute')
            ->with(Locale::class, $locale)
            ->willReturn($requestWithLocale);

        $this->handler->expects($this->once())
            ->method('handle')
            ->with($requestWithLocale)
            ->willReturn($this->response);

        $result = $this->middleware->process($this->request, $this->handler);
        $this->assertSame($this->response, $result);
    }
}