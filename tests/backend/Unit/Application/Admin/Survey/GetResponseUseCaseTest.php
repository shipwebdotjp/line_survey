<?php

declare(strict_types=1);

namespace Tests\Backend\Unit\Application\Admin\Survey;

use PHPUnit\Framework\TestCase;
use App\Application\Admin\Survey\GetResponseUseCase;
use App\Infrastructure\Database\ResponseRepository;
use App\Infrastructure\Database\SurveyRepository;
use Slim\Exception\HttpNotFoundException;
use Psr\Http\Message\ServerRequestInterface;

class GetResponseUseCaseTest extends TestCase
{
    private $surveyRepositoryMock;
    private $responseRepositoryMock;
    private $useCase;
    private $requestMock;

    protected function setUp(): void
    {
        $this->surveyRepositoryMock = $this->createMock(SurveyRepository::class);
        $this->responseRepositoryMock = $this->createMock(ResponseRepository::class);
        $this->useCase = new GetResponseUseCase($this->surveyRepositoryMock, $this->responseRepositoryMock);
        $this->requestMock = $this->createMock(ServerRequestInterface::class);
    }

    public function testExecuteSuccess(): void
    {
        $surveyId = 1;
        $responseId = 10;
        $surveyData = ['id' => $surveyId, 'title' => 'Test Survey'];
        $responseData = [
            'id' => $responseId,
            'survey_id' => $surveyId,
            'answer_json' => ['q1' => 'a1'],
            'survey_snapshot_json' => ['title' => 'Test Survey'],
            'submitted_at' => '2023-01-01 10:00:00',
            'updated_at' => '2023-01-01 10:00:00',
            'email_sent_at' => null,
            'email_error' => null,
            'respondent_name' => 'John Doe',
            'respondent_email' => 'john@example.com',
            'respondent_line_display_name' => 'JohnL',
            'respondent_honorific' => 'Mr.',
            'respondent_is_manually_entered' => 0,
            'respondent_master_id' => 100,
        ];

        $this->surveyRepositoryMock->expects($this->once())
            ->method('findById')
            ->with($surveyId)
            ->willReturn($surveyData);

        $this->responseRepositoryMock->expects($this->once())
            ->method('findByIdWithRespondent')
            ->with($responseId)
            ->willReturn($responseData);

        $result = $this->useCase->execute($surveyId, $responseId, $this->requestMock);

        $this->assertEquals($responseId, $result['id']);
        $this->assertEquals(['q1' => 'a1'], $result['answer_json']);
        $this->assertEquals('John Doe', $result['respondent']['name']);
        $this->assertEquals('Mr.', $result['respondent']['honorific']);
        $this->assertFalse($result['respondent']['is_manually_entered']);
    }

    public function testExecuteThrowsNotFoundWhenSurveyMissing(): void
    {
        $this->surveyRepositoryMock->method('findById')->willReturn(null);

        $this->expectException(HttpNotFoundException::class);
        $this->expectExceptionMessage('Survey not found');

        $this->useCase->execute(1, 10, $this->requestMock);
    }

    public function testExecuteThrowsNotFoundWhenResponseMissing(): void
    {
        $this->surveyRepositoryMock->method('findById')->willReturn(['id' => 1]);
        $this->responseRepositoryMock->method('findByIdWithRespondent')->willReturn(null);

        $this->expectException(HttpNotFoundException::class);
        $this->expectExceptionMessage('Response not found');

        $this->useCase->execute(1, 10, $this->requestMock);
    }

    public function testExecuteThrowsNotFoundWhenResponseBelongsToOtherSurvey(): void
    {
        $this->surveyRepositoryMock->method('findById')->willReturn(['id' => 1]);
        $this->responseRepositoryMock->method('findByIdWithRespondent')->willReturn([
            'id' => 10,
            'survey_id' => 2, // Different survey
        ]);

        $this->expectException(HttpNotFoundException::class);
        $this->expectExceptionMessage('Response not found');

        $this->useCase->execute(1, 10, $this->requestMock);
    }
}
