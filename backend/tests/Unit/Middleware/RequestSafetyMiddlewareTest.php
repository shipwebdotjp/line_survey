<?php

declare(strict_types=1);

namespace Tests\Unit\Middleware;

use App\Presentation\Http\Middleware\RequestSafetyMiddleware;
use App\Config\Settings;
use PHPUnit\Framework\TestCase;
use Psr\Http\Server\RequestHandlerInterface;
use Slim\Psr7\Factory\ResponseFactory;
use Slim\Psr7\Factory\ServerRequestFactory;

class RequestSafetyMiddlewareTest extends TestCase
{
    private $settings;
    private $responseFactory;
    private $middleware;

    protected function setUp(): void
    {
        $this->settings = $this->createMock(Settings::class);
        $this->responseFactory = new ResponseFactory();
        $this->middleware = new RequestSafetyMiddleware($this->settings, $this->responseFactory);
    }

    public function testAllowsSafeMethods()
    {
        $request = (new ServerRequestFactory())->createServerRequest('GET', '/api/test');
        $handler = $this->createMock(RequestHandlerInterface::class);
        $handler->expects($this->once())
            ->method('handle')
            ->willReturn($this->responseFactory->createResponse());

        $this->middleware->process($request, $handler);
    }

    public function testRejectsUnsafeMethodWithoutOriginOrReferer()
    {
        $this->settings->method('get')->with('app.origin_url')->willReturn('https://example.com');
        $request = (new ServerRequestFactory())->createServerRequest('POST', '/api/test');

        $handler = $this->createMock(RequestHandlerInterface::class);
        $handler->expects($this->never())->method('handle');

        $response = $this->middleware->process($request, $handler);
        $this->assertEquals(403, $response->getStatusCode());
    }

    public function testRejectsWhenAppUrlMissing()
    {
        $this->settings->method('get')->with('app.origin_url')->willReturn(null);
        $request = (new ServerRequestFactory())->createServerRequest('POST', '/api/test')
            ->withHeader('Origin', 'https://example.com');

        $handler = $this->createMock(RequestHandlerInterface::class);
        $handler->expects($this->never())->method('handle');

        $response = $this->middleware->process($request, $handler);
        $this->assertEquals(403, $response->getStatusCode());
    }

    public function testAllowsUnsafeMethodWithCorrectOrigin()
    {
        $this->settings->method('get')->with('app.origin_url')->willReturn('https://example.com');
        $request = (new ServerRequestFactory())->createServerRequest('POST', '/api/test')
            ->withHeader('Origin', 'https://example.com')
            ->withHeader('Content-Type', 'application/json');

        $handler = $this->createMock(RequestHandlerInterface::class);
        $handler->expects($this->once())
            ->method('handle')
            ->willReturn($this->responseFactory->createResponse());

        $response = $this->middleware->process($request, $handler);
        $this->assertEquals(200, $response->getStatusCode());
    }

    public function testAllowsOriginWithExplicitDefaultPort()
    {
        $this->settings->method('get')->with('app.origin_url')->willReturn('https://example.com');
        $request = (new ServerRequestFactory())->createServerRequest('POST', '/api/test')
            ->withHeader('Origin', 'https://example.com:443')
            ->withHeader('Content-Type', 'application/json');

        $handler = $this->createMock(RequestHandlerInterface::class);
        $handler->expects($this->once())
            ->method('handle')
            ->willReturn($this->responseFactory->createResponse());

        $response = $this->middleware->process($request, $handler);
        $this->assertEquals(200, $response->getStatusCode());
    }

    public function testRejectsUnsafeMethodWithIncorrectOrigin()
    {
        $this->settings->method('get')->with('app.origin_url')->willReturn('https://example.com');
        $request = (new ServerRequestFactory())->createServerRequest('POST', '/api/test')
            ->withHeader('Origin', 'https://malicious.com');

        $handler = $this->createMock(RequestHandlerInterface::class);
        $handler->expects($this->never())->method('handle');

        $response = $this->middleware->process($request, $handler);
        $this->assertEquals(403, $response->getStatusCode());
    }

    public function testRejectsUnsafeMethodWithIncorrectContentType()
    {
        $this->settings->method('get')->with('app.origin_url')->willReturn('https://example.com');
        $request = (new ServerRequestFactory())->createServerRequest('POST', '/api/test')
            ->withHeader('Origin', 'https://example.com')
            ->withHeader('Content-Type', 'application/x-www-form-urlencoded');

        $handler = $this->createMock(RequestHandlerInterface::class);
        $handler->expects($this->never())->method('handle');

        $response = $this->middleware->process($request, $handler);
        $this->assertEquals(415, $response->getStatusCode());
    }

    public function testRejectsContentTypeWithJsonp()
    {
        $this->settings->method('get')->with('app.origin_url')->willReturn('https://example.com');
        $request = (new ServerRequestFactory())->createServerRequest('POST', '/api/test')
            ->withHeader('Origin', 'https://example.com')
            ->withHeader('Content-Type', 'application/jsonp');

        $handler = $this->createMock(RequestHandlerInterface::class);
        $handler->expects($this->never())->method('handle');

        $response = $this->middleware->process($request, $handler);
        $this->assertEquals(415, $response->getStatusCode());
    }
}
