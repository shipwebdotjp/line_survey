<?php

declare(strict_types=1);

namespace Tests\Unit\Application\Admin\RespondentMaster;

use App\Application\Admin\RespondentMaster\UpdateRespondentMasterUseCase;
use App\Application\Admin\RespondentMaster\ValidationException;
use App\Infrastructure\Database\RespondentMasterRepository;
use PHPUnit\Framework\TestCase;

class UpdateRespondentMasterUseCaseTest extends TestCase
{
    private $repository;
    private $useCase;

    protected function setUp(): void
    {
        $this->repository = $this->createMock(RespondentMasterRepository::class);
        $this->useCase = new UpdateRespondentMasterUseCase($this->repository);
    }

    public function testExecuteSuccess(): void
    {
        $id = 1;
        $ownerUserId = 1;
        $data = [
            'master_code' => 'M001',
            'line_display_name' => 'User One',
            'name' => 'Name One Updated',
            'email' => 'one@example.com'
        ];

        $this->repository->method('findById')->with($id, $ownerUserId)->willReturn(['id' => $id]);

        $this->repository->method('findBy')
            ->willReturnMap([
                [['master_code' => 'M001'], $ownerUserId, [['id' => 1]]],
                [['line_display_name' => 'User One'], $ownerUserId, [['id' => 1]]],
            ]);

        $this->repository->expects($this->once())
            ->method('update')
            ->with($id, array_merge($data, ['honorific' => null, 'note' => null]), $ownerUserId)
            ->willReturn(true);

        $success = $this->useCase->execute($id, $data, $ownerUserId);
        $this->assertTrue($success);
    }

    public function testExecuteNotFound(): void
    {
        $id = 999;
        $ownerUserId = 1;
        $this->repository->method('findById')->with($id, $ownerUserId)->willReturn(null);

        $success = $this->useCase->execute($id, [], $ownerUserId);
        $this->assertFalse($success);
    }
}
