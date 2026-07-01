<?php

declare(strict_types=1);

namespace Tests\Unit\Application\Admin\Survey;

use App\Application\Admin\Survey\UpdateResponseUseCase;
use App\Infrastructure\Database\ResponseRepository;
use App\Infrastructure\Database\SurveyRepository;
use PHPUnit\Framework\TestCase;
use Slim\Exception\HttpNotFoundException;
use Psr\Http\Message\ServerRequestInterface as Request;

class UpdateResponseUseCaseTest extends TestCase
{
    private $surveyRepository;
    private $responseRepository;
    private $useCase;
    private $request;

    protected function setUp(): void
    {
        $this->surveyRepository = $this->createMock(SurveyRepository::class);
        $this->responseRepository = $this->createMock(ResponseRepository::class);
        $this->useCase = new UpdateResponseUseCase($this->surveyRepository, $this->responseRepository);
        $this->request = $this->createMock(Request::class);
    }

    public function testExecuteUpdatesResponseSuccessfully(): void
    {
        $surveyId = 1;
        $responseId = 10;
        $ownerUserId = 123;
        $answerJson = ['q1' => 'ans1'];

        $this->surveyRepository->method('findById')->with($surveyId, $ownerUserId)->willReturn(['id' => $surveyId]);
        $this->responseRepository->method('findById')->with($responseId)->willReturn([
            'id' => $responseId,
            'survey_id' => $surveyId
        ]);

        $this->responseRepository->expects($this->once())
            ->method('update')
            ->with($responseId, ['answer_json' => $answerJson]);

        $updatedResponse = [
            'id' => $responseId,
            'answer_json' => $answerJson,
            'respondent_name' => 'Test User'
        ];
        $this->responseRepository->method('findByIdWithRespondent')->with($responseId)->willReturn($updatedResponse);

        $result = $this->useCase->execute($surveyId, $responseId, $answerJson, $ownerUserId, $this->request);

        $this->assertEquals($updatedResponse, $result);
    }

    public function testExecuteThrowsNotFoundWhenSurveyMissing(): void
    {
        $this->surveyRepository->method('findById')->willReturn(null);

        $this->expectException(HttpNotFoundException::class);
        $this->useCase->execute(1, 10, [], 123, $this->request);
    }

    public function testExecuteThrowsNotFoundWhenResponseMissing(): void
    {
        $this->surveyRepository->method('findById')->willReturn(['id' => 1]);
        $this->responseRepository->method('findById')->willReturn(null);

        $this->expectException(HttpNotFoundException::class);
        $this->useCase->execute(1, 10, [], 123, $this->request);
    }

    public function testExecuteThrowsNotFoundWhenResponseBelongsToDifferentSurvey(): void
    {
        $this->surveyRepository->method('findById')->willReturn(['id' => 1]);
        $this->responseRepository->method('findById')->willReturn([
            'id' => 10,
            'survey_id' => 2
        ]);

        $this->expectException(HttpNotFoundException::class);
        $this->useCase->execute(1, 10, [], 123, $this->request);
    }
}
