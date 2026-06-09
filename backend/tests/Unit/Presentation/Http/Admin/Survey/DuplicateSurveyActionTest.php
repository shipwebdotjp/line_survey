<?php

declare(strict_types=1);

namespace Tests\Unit\Presentation\Http\Admin\Survey;

use App\Application\Admin\Survey\DuplicateSurveyUseCase;
use App\Presentation\Http\Admin\Survey\DuplicateSurveyAction;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Slim\Psr7\Factory\ResponseFactory;
use RuntimeException;

class DuplicateSurveyActionTest extends TestCase
{
    private $useCase;
    private $action;
    private $request;
    private $response;

    protected function setUp(): void
    {
        $this->useCase = $this->createMock(DuplicateSurveyUseCase::class);
        $this->action = new DuplicateSurveyAction($this->useCase);
        $this->request = $this->createMock(ServerRequestInterface::class);
        $this->response = (new ResponseFactory())->createResponse();
    }

    public function testInvokeReturns201OnSuccess(): void
    {
        $sourceId = 1;
        $newId = 2;

        $this->useCase->expects($this->once())
            ->method('execute')
            ->with($sourceId)
            ->willReturn($newId);

        $result = ($this->action)($this->request, $this->response, ['id' => (string)$sourceId]);

        $this->assertEquals(201, $result->getStatusCode());
        $payload = json_decode((string)$result->getBody(), true);
        $this->assertEquals(['data' => ['id' => $newId]], $payload);
    }

    public function testInvokeReturns404WhenSurveyNotFound(): void
    {
        $sourceId = 999;

        $this->useCase->expects($this->once())
            ->method('execute')
            ->with($sourceId)
            ->willThrowException(new RuntimeException('Survey not found', 404));

        $result = ($this->action)($this->request, $this->response, ['id' => (string)$sourceId]);

        $this->assertEquals(404, $result->getStatusCode());
        $payload = json_decode((string)$result->getBody(), true);
        $this->assertEquals('NOT_FOUND', $payload['code']);
    }

    public function testInvokeReturns500OnInternalError(): void
    {
        $sourceId = 1;

        $this->useCase->expects($this->once())
            ->method('execute')
            ->with($sourceId)
            ->willThrowException(new \Exception('Internal error'));

        $result = ($this->action)($this->request, $this->response, ['id' => (string)$sourceId]);

        $this->assertEquals(500, $result->getStatusCode());
        $payload = json_decode((string)$result->getBody(), true);
        $this->assertEquals('INTERNAL_ERROR', $payload['code']);
    }

    public function testInvokeReturns500OnRuntimeExceptionWithNon404Code(): void
    {
        $sourceId = 1;

        $this->useCase->expects($this->once())
            ->method('execute')
            ->with($sourceId)
            ->willThrowException(new RuntimeException('Unexpected error', 500));

        $result = ($this->action)($this->request, $this->response, ['id' => (string)$sourceId]);

        $this->assertEquals(500, $result->getStatusCode());
        $payload = json_decode((string)$result->getBody(), true);
        $this->assertEquals('INTERNAL_ERROR', $payload['code']);
    }
}
