<?php

declare(strict_types=1);

namespace Tests\Unit\Application\Admin\Survey;

use App\Application\Admin\Survey\CleanupResponseDraftsUseCase;
use App\Infrastructure\Database\ResponseDraftRepository;
use PHPUnit\Framework\TestCase;

class CleanupResponseDraftsUseCaseTest extends TestCase
{
    public function testExecuteCallsDeleteExpiredBefore(): void
    {
        $repository = $this->createMock(ResponseDraftRepository::class);
        $repository->expects($this->once())
            ->method('deleteExpiredBefore')
            ->willReturn(5);

        $useCase = new CleanupResponseDraftsUseCase($repository);
        $result = $useCase->execute();

        $this->assertEquals(5, $result);
    }
}
