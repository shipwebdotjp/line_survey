<?php

declare(strict_types=1);

namespace Tests\Unit\Admin\Survey;

use App\Application\Admin\Survey\ListResponsesUseCase;
use App\Infrastructure\Database\ResponseRepository;
use App\Infrastructure\Database\SurveyRepository;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ServerRequestInterface;

class ListResponsesUseCaseTest extends TestCase
{
    private \App\Infrastructure\Database\SurveyRepository $surveyRepository;
    private \App\Infrastructure\Database\ResponseRepository $responseRepository;
    private \App\Application\Admin\Survey\ListResponsesUseCase $useCase;
    private \Psr\Http\Message\ServerRequestInterface $request;

    protected function setUp(): void
    {
        $this->surveyRepository = $this->createMock(SurveyRepository::class);
        $this->responseRepository = $this->createMock(ResponseRepository::class);
        $this->useCase = new ListResponsesUseCase($this->surveyRepository, $this->responseRepository);
        $this->request = $this->createMock(ServerRequestInterface::class);
    }

    public function testExecuteReturnsFormattedResponses(): void
    {
        $surveyId = 1;
        $survey = ['id' => $surveyId];
        $responses = [
            [
                'id' => 101,
                'respondent_name' => 'John Doe',
                'respondent_email' => 'john@example.com',
                'respondent_line_display_name' => 'JohnL',
                'respondent_honorific' => 'Mr.',
                'submitted_at' => '2023-10-01 10:00:00',
                'updated_at' => '2023-10-01 10:00:00',
                'email_sent_at' => '2023-10-01 10:00:05',
                'email_error' => null,
            ]
        ];

        $this->surveyRepository->expects($this->once())
            ->method('findById')
            ->with($surveyId)
            ->willReturn($survey);

        $this->responseRepository->expects($this->once())
            ->method('findBySurveyIdWithRespondent')
            ->with($surveyId)
            ->willReturn($responses);

        $result = $this->useCase->execute($surveyId, $this->request);

        $this->assertCount(1, $result);
        $this->assertEquals(101, $result[0]['id']);
        $this->assertEquals('John Doe', $result[0]['respondent_name']);
        $this->assertEquals('2023-10-01 10:00:00', $result[0]['submitted_at']);
        $this->assertEquals('2023-10-01 10:00:05', $result[0]['email_sent_at']);
    }

    public function testExecuteThrowsNotFoundExceptionIfSurveyDoesNotExist(): void
    {
        $surveyId = 999;
        $this->surveyRepository->method('findById')->willReturn(null);

        $this->expectException(\Slim\Exception\HttpNotFoundException::class);
        $this->useCase->execute($surveyId, $this->request);
    }

    public function testExecuteHandlesNullEmailSentAt(): void
    {
        $surveyId = 1;
        $survey = ['id' => $surveyId];
        $responses = [
            [
                'id' => 102,
                'respondent_name' => 'Jane Doe',
                'respondent_email' => 'jane@example.com',
                'respondent_line_display_name' => 'JaneL',
                'respondent_honorific' => 'Ms.',
                'submitted_at' => '2023-10-01 12:00:00',
                'updated_at' => '2023-10-01 12:00:00',
                'email_sent_at' => null,
                'email_error' => 'Failed to send',
            ]
        ];

        $this->surveyRepository->expects($this->once())
            ->method('findById')
            ->with($surveyId)
            ->willReturn($survey);

        $this->responseRepository->expects($this->once())
            ->method('findBySurveyIdWithRespondent')
            ->with($surveyId)
            ->willReturn($responses);

        $result = $this->useCase->execute($surveyId, $this->request);

        $this->assertCount(1, $result);
        $this->assertEquals(102, $result[0]['id']);
        $this->assertNull($result[0]['email_sent_at']);
        $this->assertEquals('Failed to send', $result[0]['email_error']);
    }
}
