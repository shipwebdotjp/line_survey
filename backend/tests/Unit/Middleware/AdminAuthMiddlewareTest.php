<?php

declare(strict_types=1);

namespace Tests\Unit\Middleware;

use App\Infrastructure\Database\UserRepository;
use App\Presentation\Http\Middleware\AdminAuthMiddleware;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Slim\Psr7\Factory\ResponseFactory;

class AdminAuthMiddlewareTest extends TestCase
{
    private $userRepository;
    private $responseFactory;
    private $middleware;
    private $request;
    private $handler;

    protected function setUp(): void
    {
        $this->userRepository = $this->createMock(UserRepository::class);
        $this->responseFactory = new ResponseFactory();
        $this->middleware = new AdminAuthMiddleware($this->userRepository, $this->responseFactory);
        $this->request = $this->createMock(ServerRequestInterface::class);
        $this->handler = $this->createMock(RequestHandlerInterface::class);

        if (session_status() !== PHP_SESSION_ACTIVE) {
            @session_start();
        }
        $_SESSION = [];
    }

    public function testReturns401IfNoOwnerUserIdInSession(): void
    {
        $response = $this->middleware->process($this->request, $this->handler);

        $this->assertEquals(401, $response->getStatusCode());
        $body = (string)$response->getBody();
        $this->assertStringContainsString('OWNER_SESSION_REQUIRED', $body);
    }

    public function testReturns401IfUserNotFound(): void
    {
        $_SESSION['owner_user_id'] = 1;
        $this->userRepository->method('findById')->with(1)->willReturn(null);

        $response = $this->middleware->process($this->request, $this->handler);

        $this->assertEquals(401, $response->getStatusCode());
        $this->assertArrayNotHasKey('owner_user_id', $_SESSION);
    }

    public function testCallsHandlerIfUserFound(): void
    {
        $_SESSION['owner_user_id'] = 1;
        $user = ['id' => 1, 'line_display_name' => 'Admin'];
        $this->userRepository->method('findById')->with(1)->willReturn($user);

        $this->request->expects($this->once())
            ->method('withAttribute')
            ->with('owner_user', $user)
            ->willReturn($this->request);

        $this->handler->expects($this->once())
            ->method('handle')
            ->with($this->request)
            ->willReturn($this->createMock(ResponseInterface::class));

        $this->middleware->process($this->request, $this->handler);
    }
}
