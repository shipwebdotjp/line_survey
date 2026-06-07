<?php

namespace Tests\Backend\Unit\Presentation\Http\Middleware;

use App\Config\Settings;
use App\Presentation\Http\Middleware\BasicAuthMiddleware;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Slim\Psr7\Response;

class BasicAuthMiddlewareTest extends TestCase
{
    private $settings;
    private $middleware;
    private $request;
    private $handler;

    protected function setUp(): void
    {
        $this->settings = $this->createMock(Settings::class);
        $this->middleware = new BasicAuthMiddleware($this->settings);
        $this->request = $this->createMock(ServerRequestInterface::class);
        $this->handler = $this->createMock(RequestHandlerInterface::class);
    }

    public function testProcessReturns401WhenAuthorizationHeaderIsMissing()
    {
        $this->request->method('getHeaderLine')->with('Authorization')->willReturn('');

        $response = $this->middleware->process($this->request, $this->handler);

        $this->assertEquals(401, $response->getStatusCode());
        $this->assertEquals('Basic realm="Admin"', $response->getHeaderLine('WWW-Authenticate'));

        $body = (string)$response->getBody();
        $this->assertStringContainsString('UNAUTHORIZED', $body);
    }

    public function testProcessReturns401WhenCredentialsAreInvalid()
    {
        $this->settings->method('get')->willReturnMap([
            ['auth.admin_user', null, 'admin'],
            ['auth.admin_pass', null, 'password'],
        ]);

        // admin:wrong -> YWRtaW46d3Jvbmc=
        $this->request->method('getHeaderLine')->with('Authorization')->willReturn('Basic YWRtaW46d3Jvbmc=');

        $response = $this->middleware->process($this->request, $this->handler);

        $this->assertEquals(401, $response->getStatusCode());
    }

    public function testProcessCallsHandlerWhenCredentialsAreValid()
    {
        $this->settings->method('get')->willReturnMap([
            ['auth.admin_user', null, 'admin'],
            ['auth.admin_pass', null, 'password'],
        ]);

        // admin:password -> YWRtaW46cGFzc3dvcmQ=
        $this->request->method('getHeaderLine')->with('Authorization')->willReturn('Basic YWRtaW46cGFzc3dvcmQ=');

        $expectedResponse = new Response();
        $this->handler->method('handle')->with($this->request)->willReturn($expectedResponse);

        $response = $this->middleware->process($this->request, $this->handler);

        $this->assertSame($expectedResponse, $response);
    }
}
