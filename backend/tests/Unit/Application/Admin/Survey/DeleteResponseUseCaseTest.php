<?php

declare(strict_types=1);

namespace Tests\Unit\Application\Admin\Survey;

use App\Application\Admin\Survey\DeleteResponseUseCase;
use App\Infrastructure\Database\ResponseRepository;
use App\Infrastructure\Database\SurveyRepository;
use PHPUnit\Framework\TestCase;
use Slim\Exception\HttpNotFoundException;
use Psr\Http\Message\ServerRequestInterface as Request;

class DeleteResponseUseCaseTest extends TestCase
{
    private $surveyRepository;
    private $responseRepository;
    private $useCase;
    private $request;

    protected function setUp(): void
    {
        $this->surveyRepository = $this->createMock(SurveyRepository::class);
        $this->responseRepository = $this->createMock(ResponseRepository::class);
        $this->useCase = new DeleteResponseUseCase($this->surveyRepository, $this->responseRepository);
        $this->request = $this->createMock(Request::class);
    }

    public function testExecuteDeletesResponseSuccessfully(): void
    {
        $surveyId = 1;
        $responseId = 10;

        $this->surveyRepository->method('findById')->with($surveyId)->willReturn(['id' => $surveyId]);
        $this->responseRepository->method('findById')->with($responseId)->willReturn([
            'id' => $responseId,
            'survey_id' => $surveyId
        ]);

        $this->responseRepository->expects($this->once())
            ->method('delete')
            ->with($responseId)
            ->willReturn(true);

        $this->useCase->execute($surveyId, $responseId, $this->request);
    }

    public function testExecuteThrowsNotFoundWhenSurveyMissing(): void
    {
        $this->surveyRepository->method('findById')->willReturn(null);

        $this->expectException(HttpNotFoundException::class);
        $this->useCase->execute(1, 10, $this->request);
    }

    public function testExecuteThrowsNotFoundWhenResponseMissing(): void
    {
        $this->surveyRepository->method('findById')->willReturn(['id' => 1]);
        $this->responseRepository->method('findById')->willReturn(null);

        $this->expectException(HttpNotFoundException::class);
        $this->useCase->execute(1, 10, $this->request);
    }

    public function testExecuteThrowsNotFoundWhenResponseBelongsToDifferentSurvey(): void
    {
        $this->surveyRepository->method('findById')->willReturn(['id' => 1]);
        $this->responseRepository->method('findById')->willReturn([
            'id' => 10,
            'survey_id' => 2
        ]);

        $this->expectException(HttpNotFoundException::class);
        $this->useCase->execute(1, 10, $this->request);
    }
}
