<?php

declare(strict_types=1);

namespace Tests\Unit\Application\Admin\RespondentMaster;

use App\Application\Admin\RespondentMaster\DeleteRespondentMasterUseCase;
use App\Infrastructure\Database\RespondentMasterRepository;
use PHPUnit\Framework\TestCase;

class DeleteRespondentMasterUseCaseTest extends TestCase
{
    private $repository;
    private $useCase;

    protected function setUp(): void
    {
        $this->repository = $this->createMock(RespondentMasterRepository::class);
        $this->useCase = new DeleteRespondentMasterUseCase($this->repository);
    }

    public function testExecuteSuccess(): void
    {
        $id = 1;
        $this->repository->expects($this->once())
            ->method('delete')
            ->with($id)
            ->willReturn(true);

        $success = $this->useCase->execute($id);
        $this->assertTrue($success);
    }

    public function testExecuteNotFound(): void
    {
        $id = 999;
        $this->repository->method('delete')->with($id)->willReturn(false);

        $success = $this->useCase->execute($id);
        $this->assertFalse($success);
    }
}
