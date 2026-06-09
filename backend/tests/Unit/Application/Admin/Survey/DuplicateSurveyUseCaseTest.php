<?php

declare(strict_types=1);

namespace Tests\Unit\Application\Admin\Survey;

use App\Application\Admin\Survey\DuplicateSurveyUseCase;
use App\Infrastructure\Database\SurveyRepository;
use PHPUnit\Framework\TestCase;
use RuntimeException;

class DuplicateSurveyUseCaseTest extends TestCase
{
    private $surveyRepository;
    private $useCase;

    protected function setUp(): void
    {
        $this->surveyRepository = $this->createMock(SurveyRepository::class);
        $this->useCase = new DuplicateSurveyUseCase($this->surveyRepository);
    }

    public function testExecuteDuplicatesSurveySuccessfully(): void
    {
        $sourceId = 1;
        $sourceSurvey = [
            'id' => $sourceId,
            'public_id' => 'sv_source',
            'title' => 'Source Survey',
            'description' => 'Source Description',
            'questions_json' => [['type' => 'text', 'title' => 'Q1']],
            'status' => 'published',
            'allow_multiple' => true,
            'allow_edit' => true,
            'starts_at' => '2023-01-01 00:00:00',
            'ends_at' => '2023-12-31 23:59:59',
            'send_confirmation_email' => true,
            'include_answers_in_email' => true,
            'created_at' => '2023-01-01 00:00:00',
            'updated_at' => '2023-01-01 00:00:00',
        ];

        $this->surveyRepository->expects($this->once())
            ->method('findById')
            ->with($sourceId)
            ->willReturn($sourceSurvey);

        $this->surveyRepository->expects($this->once())
            ->method('save')
            ->with($this->callback(function (array $data) use ($sourceSurvey) {
                return $data['title'] === $sourceSurvey['title'] &&
                       $data['description'] === $sourceSurvey['description'] &&
                       $data['questions_json'] === $sourceSurvey['questions_json'] &&
                       $data['status'] === 'draft' &&
                       $data['allow_multiple'] === true &&
                       $data['allow_edit'] === true &&
                       $data['send_confirmation_email'] === true &&
                       $data['include_answers_in_email'] === true &&
                       str_starts_with($data['public_id'], 'sv_') &&
                       $data['public_id'] !== $sourceSurvey['public_id'] &&
                       !isset($data['id']) &&
                       !isset($data['starts_at']) &&
                       !isset($data['ends_at']) &&
                       !isset($data['created_at']) &&
                       !isset($data['updated_at']);
            }))
            ->willReturn(2);

        $newId = $this->useCase->execute($sourceId);

        $this->assertEquals(2, $newId);
    }

    public function testExecuteHandlesEmptyTitle(): void
    {
        $sourceId = 1;
        $sourceSurvey = [
            'id' => $sourceId,
            'public_id' => 'sv_source',
            'title' => '   ',
            'description' => 'Source Description',
            'questions_json' => [],
            'status' => 'published',
            'allow_multiple' => true,
            'allow_edit' => true,
            'send_confirmation_email' => true,
            'include_answers_in_email' => true,
        ];

        $this->surveyRepository->expects($this->once())
            ->method('findById')
            ->with($sourceId)
            ->willReturn($sourceSurvey);

        $this->surveyRepository->expects($this->once())
            ->method('save')
            ->with($this->callback(function (array $data) {
                return $data['title'] === 'Untitled Survey';
            }))
            ->willReturn(3);

        $newId = $this->useCase->execute($sourceId);
        $this->assertEquals(3, $newId);
    }

    public function testExecuteThrowsExceptionIfSurveyNotFound(): void
    {
        $sourceId = 999;

        $this->surveyRepository->expects($this->once())
            ->method('findById')
            ->with($sourceId)
            ->willReturn(null);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Survey not found');
        $this->expectExceptionCode(404);

        $this->useCase->execute($sourceId);
    }
}
