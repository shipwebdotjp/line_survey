<?php

declare(strict_types=1);

namespace Tests\Unit\Application\Admin\Survey;

use App\Application\Admin\Survey\GetResponseDraftAdminUseCase;
use App\Infrastructure\Database\ResponseDraftRepository;
use PHPUnit\Framework\TestCase;

class GetResponseDraftAdminUseCaseTest extends TestCase
{
    public function testExecuteReturnsDraft(): void
    {
        $repository = $this->createMock(ResponseDraftRepository::class);
        $repository->expects($this->once())
            ->method('findById')
            ->with(1, 123)
            ->willReturn(['id' => 1, 'survey_title' => 'Test Survey']);

        $useCase = new GetResponseDraftAdminUseCase($repository);
        $result = $useCase->execute(1, 123);

        $this->assertEquals('Test Survey', $result['survey_title']);
    }

    public function testExecuteReturnsNullIfNotFound(): void
    {
        $repository = $this->createMock(ResponseDraftRepository::class);
        $repository->expects($this->once())
            ->method('findById')
            ->with(1, 123)
            ->willReturn(null);

        $useCase = new GetResponseDraftAdminUseCase($repository);
        $result = $useCase->execute(1, 123);

        $this->assertNull($result);
    }
}
