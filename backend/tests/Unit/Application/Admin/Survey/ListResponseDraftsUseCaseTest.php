<?php

declare(strict_types=1);

namespace Tests\Unit\Application\Admin\Survey;

use App\Application\Admin\Survey\ListResponseDraftsUseCase;
use App\Infrastructure\Database\ResponseDraftRepository;
use PHPUnit\Framework\TestCase;

class ListResponseDraftsUseCaseTest extends TestCase
{
    public function testExecuteReturnsDrafts(): void
    {
        $repository = $this->createMock(ResponseDraftRepository::class);
        $repository->expects($this->once())
            ->method('findAll')
            ->willReturn([['id' => 1, 'survey_title' => 'Test Survey']]);

        $useCase = new ListResponseDraftsUseCase($repository);
        $result = $useCase->execute();

        $this->assertCount(1, $result);
        $this->assertEquals('Test Survey', $result[0]['survey_title']);
    }
}
