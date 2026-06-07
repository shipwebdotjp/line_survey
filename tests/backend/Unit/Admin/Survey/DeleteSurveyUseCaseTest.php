<?php

declare(strict_types=1);

namespace Tests\Backend\Unit\Admin\Survey;

use App\Application\Admin\Survey\DeleteSurveyUseCase;
use App\Infrastructure\Database\ResponseRepository;
use App\Infrastructure\Database\SurveyRepository;
use PHPUnit\Framework\TestCase;

class DeleteSurveyUseCaseTest extends TestCase
{
    public function testExecuteSuccess()
    {
        $surveyRepo = $this->createMock(SurveyRepository::class);
        $responseRepo = $this->createMock(ResponseRepository::class);

        $surveyRepo->expects($this->once())
            ->method('findById')
            ->with(1)
            ->willReturn(['id' => 1]);

        $responseRepo->expects($this->once())
            ->method('countBySurveyId')
            ->with(1)
            ->willReturn(0);

        $surveyRepo->expects($this->once())
            ->method('delete')
            ->with(1)
            ->willReturn(true);

        $useCase = new DeleteSurveyUseCase($surveyRepo, $responseRepo);
        $result = $useCase->execute(1);

        $this->assertTrue($result);
    }

    public function testExecuteThrowsExceptionWhenNotFound()
    {
        $surveyRepo = $this->createMock(SurveyRepository::class);
        $responseRepo = $this->createMock(ResponseRepository::class);

        $surveyRepo->expects($this->once())
            ->method('findById')
            ->with(1)
            ->willReturn(null);

        $useCase = new DeleteSurveyUseCase($surveyRepo, $responseRepo);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionCode(404);
        $this->expectExceptionMessage('Survey not found');

        $useCase->execute(1);
    }

    public function testExecuteThrowsExceptionWhenResponsesExist()
    {
        $surveyRepo = $this->createMock(SurveyRepository::class);
        $responseRepo = $this->createMock(ResponseRepository::class);

        $surveyRepo->expects($this->once())
            ->method('findById')
            ->with(1)
            ->willReturn(['id' => 1]);

        $responseRepo->expects($this->once())
            ->method('countBySurveyId')
            ->with(1)
            ->willReturn(5);

        $surveyRepo->expects($this->never())
            ->method('delete');

        $useCase = new DeleteSurveyUseCase($surveyRepo, $responseRepo);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionCode(409);
        $this->expectExceptionMessage('Cannot delete survey with responses');

        $useCase->execute(1);
    }
}
