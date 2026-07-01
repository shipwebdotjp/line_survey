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
        $ownerUserId = 123;

        $this->surveyRepository->method('findById')->with($surveyId, $ownerUserId)->willReturn(['id' => $surveyId]);
        $this->responseRepository->method('findById')->with($responseId)->willReturn([
            'id' => $responseId,
            'survey_id' => $surveyId
        ]);

        $this->responseRepository->expects($this->once())
            ->method('delete')
            ->with($responseId)
            ->willReturn(true);

        $this->useCase->execute($surveyId, $responseId, $ownerUserId, $this->request);
    }

    public function testExecuteThrowsNotFoundWhenSurveyMissing(): void
    {
        $surveyId = 1;
        $ownerUserId = 123;
        $this->surveyRepository->expects($this->once())
            ->method('findById')
            ->with($surveyId, $ownerUserId)
            ->willReturn(null);

        $this->expectException(HttpNotFoundException::class);
        $this->useCase->execute($surveyId, 10, $ownerUserId, $this->request);
    }

    public function testExecuteThrowsNotFoundWhenResponseMissing(): void
    {
        $surveyId = 1;
        $ownerUserId = 123;
        $this->surveyRepository->expects($this->once())
            ->method('findById')
            ->with($surveyId, $ownerUserId)
            ->willReturn(['id' => $surveyId]);
        $this->responseRepository->method('findById')->willReturn(null);

        $this->expectException(HttpNotFoundException::class);
        $this->useCase->execute($surveyId, 10, $ownerUserId, $this->request);
    }

    public function testExecuteThrowsNotFoundWhenResponseBelongsToDifferentSurvey(): void
    {
        $surveyId = 1;
        $ownerUserId = 123;
        $this->surveyRepository->expects($this->once())
            ->method('findById')
            ->with($surveyId, $ownerUserId)
            ->willReturn(['id' => $surveyId]);
        $this->responseRepository->method('findById')->willReturn([
            'id' => 10,
            'survey_id' => 2
        ]);

        $this->expectException(HttpNotFoundException::class);
        $this->useCase->execute(1, 10, 123, $this->request);
    }
}
