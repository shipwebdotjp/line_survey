<?php

declare(strict_types=1);

namespace Tests\Unit\Application\Survey;

use App\Application\Survey\GetResponseHistoryUseCase;
use App\Infrastructure\Database\ResponseRepository;
use PHPUnit\Framework\TestCase;

class GetResponseHistoryUseCaseTest extends TestCase
{
    private $responseRepository;
    private $useCase;

    protected function setUp(): void
    {
        $this->responseRepository = $this->createMock(ResponseRepository::class);
        $this->useCase = new GetResponseHistoryUseCase($this->responseRepository);
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
            ->with(123)
            ->willReturn($expectedHistory);

        $result = $this->useCase->execute($respondent);

        $this->assertEquals($expectedHistory, $result);
    }

    public function testExecuteThrowsExceptionIfRespondentMissing(): void
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Respondent not found');
        $this->expectExceptionCode(404);

        $this->useCase->execute([]);
    }
}
