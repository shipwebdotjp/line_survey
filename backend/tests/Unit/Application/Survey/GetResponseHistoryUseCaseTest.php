<?php

declare(strict_types=1);

namespace Tests\Unit\Application\Survey;

use App\Application\Survey\GetResponseHistoryUseCase;
use App\Infrastructure\Database\ResponseRepository;
use App\Infrastructure\Database\SurveyRepository;
use PHPUnit\Framework\TestCase;

class GetResponseHistoryUseCaseTest extends TestCase
{
    private $surveyRepository;
    private $responseRepository;
    private $useCase;

    protected function setUp(): void
    {
        $this->surveyRepository = $this->createMock(SurveyRepository::class);
        $this->responseRepository = $this->createMock(ResponseRepository::class);
        $this->useCase = new GetResponseHistoryUseCase($this->surveyRepository, $this->responseRepository);
    }

    public function testExecuteReturnsHistory(): void
    {
        $respondent = ['id' => 123];
        $expectedHistory = [
            [
                'submitted_at' => '2023-01-02 10:00:00',
                'updated_at' => '2023-01-02 10:00:00',
                'survey_public_id' => 'survey1',
                'survey_title' => 'Survey 1'
            ],
            [
                'submitted_at' => '2023-01-01 10:00:00',
                'updated_at' => '2023-01-01 10:00:00',
                'survey_public_id' => 'survey2',
                'survey_title' => 'Survey 2'
            ]
        ];

        $this->responseRepository->expects($this->once())
            ->method('findHistoryByRespondentId')
            ->with(123, null)
            ->willReturn($expectedHistory);

        $result = $this->useCase->execute($respondent);

        $this->assertEquals($expectedHistory, $result);
    }

    public function testExecuteReturnsFilteredHistory(): void
    {
        $respondent = ['id' => 123];
        $surveyPublicId = 'survey1';
        $surveyId = 456;
        $expectedHistory = [
            [
                'submitted_at' => '2023-01-02 10:00:00',
                'updated_at' => '2023-01-02 10:00:00',
                'survey_public_id' => 'survey1',
                'survey_title' => 'Survey 1'
            ]
        ];

        $this->surveyRepository->expects($this->once())
            ->method('findByPublicId')
            ->with($surveyPublicId)
            ->willReturn(['id' => $surveyId]);

        $this->responseRepository->expects($this->once())
            ->method('findHistoryByRespondentId')
            ->with(123, $surveyId)
            ->willReturn($expectedHistory);

        $result = $this->useCase->execute($respondent, $surveyPublicId);

        $this->assertEquals($expectedHistory, $result);
    }

    public function testExecuteThrowsExceptionWhenSurveyNotFound(): void
    {
        $respondent = ['id' => 123];
        $surveyPublicId = 'non-existent';

        $this->surveyRepository->expects($this->once())
            ->method('findByPublicId')
            ->with($surveyPublicId)
            ->willReturn(null);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Survey not found');
        $this->expectExceptionCode(404);

        $this->useCase->execute($respondent, $surveyPublicId);
    }

    public function testExecuteThrowsExceptionIfRespondentMissing(): void
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Respondent not found');
        $this->expectExceptionCode(404);

        $this->useCase->execute([]);
    }
}
