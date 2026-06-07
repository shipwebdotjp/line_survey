<?php

namespace Tests\Backend\Unit\Admin\Survey;

use App\Application\Admin\Survey\UpdateSurveyUseCase;
use App\Infrastructure\Database\SurveyRepository;
use PHPUnit\Framework\TestCase;

class UpdateSurveyUseCaseTest extends TestCase
{
    public function testExecuteSuccess()
    {
        $surveyRepo = $this->createMock(SurveyRepository::class);

        $surveyRepo->expects($this->once())
            ->method('findById')
            ->with(1)
            ->willReturn(['id' => 1, 'title' => 'Old Title']);

        $surveyRepo->expects($this->once())
            ->method('update')
            ->with(1, ['title' => 'New Title'])
            ->willReturn(true);

        $useCase = new UpdateSurveyUseCase($surveyRepo);
        $result = $useCase->execute(1, ['title' => 'New Title', 'public_id' => 'should_be_ignored']);

        $this->assertTrue($result);
    }

    public function testExecuteThrowsExceptionWhenNotFound()
    {
        $surveyRepo = $this->createMock(SurveyRepository::class);

        $surveyRepo->expects($this->once())
            ->method('findById')
            ->with(1)
            ->willReturn(null);

        $useCase = new UpdateSurveyUseCase($surveyRepo);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionCode(404);
        $this->expectExceptionMessage('Survey not found');

        $useCase->execute(1, ['title' => 'New Title']);
    }
}
