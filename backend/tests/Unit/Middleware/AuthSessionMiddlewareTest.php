<?php

declare(strict_types=1);

namespace Tests\Unit\Middleware;

use App\Presentation\Http\Middleware\AuthSessionMiddleware;
use App\Infrastructure\Database\RespondentRepository;
use PHPUnit\Framework\TestCase;
use Psr\Http\Server\RequestHandlerInterface;
use Slim\Psr7\Factory\ResponseFactory;
use Slim\Psr7\Factory\ServerRequestFactory;

class AuthSessionMiddlewareTest extends TestCase
{
    private $repository;
    private $responseFactory;
    private $middleware;

    protected function setUp(): void
    {
        $this->repository = $this->createMock(RespondentRepository::class);
        $this->responseFactory = new ResponseFactory();
        $this->middleware = new AuthSessionMiddleware($this->repository, $this->responseFactory);

        if (session_status() === PHP_SESSION_ACTIVE) {
            session_destroy();
        }
    }

    public function testRejectsWithoutSession()
    {
        $_SESSION = [];
        $request = (new ServerRequestFactory())->createServerRequest('GET', '/api/test');
        $handler = $this->createMock(RequestHandlerInterface::class);
        $handler->expects($this->never())->method('handle');

        $response = $this->middleware->process($request, $handler);
        $this->assertEquals(401, $response->getStatusCode());
        $this->assertStringContainsString('SESSION_REQUIRED', (string)$response->getBody());
    }

    public function testAllowsWithValidSession()
    {
        $_SESSION['respondent_id'] = 123;
        $respondent = ['id' => 123, 'name' => 'John Doe'];
        $this->repository->method('findById')->with(123)->willReturn($respondent);

        $request = (new ServerRequestFactory())->createServerRequest('GET', '/api/test');
        $handler = $this->createMock(RequestHandlerInterface::class);
        $handler->expects($this->once())
            ->method('handle')
            ->with($this->callback(function ($req) use ($respondent) {
                return $req->getAttribute('respondent') === $respondent;
            }))
            ->willReturn($this->responseFactory->createResponse());

        $response = $this->middleware->process($request, $handler);
        $this->assertEquals(200, $response->getStatusCode());
    }

    public function testRejectsWhenRespondentNotFound()
    {
        $_SESSION['respondent_id'] = 999;
        $this->repository->method('findById')->with(999)->willReturn(null);

        $request = (new ServerRequestFactory())->createServerRequest('GET', '/api/test');
        $handler = $this->createMock(RequestHandlerInterface::class);
        $handler->expects($this->never())->method('handle');

        $response = $this->middleware->process($request, $handler);
        $this->assertEquals(401, $response->getStatusCode());
        $this->assertArrayNotHasKey('respondent_id', $_SESSION);
    }
}
