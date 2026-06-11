<?php

declare(strict_types=1);

namespace Tests\Unit\Application\Admin\RespondentMaster;

use App\Application\Admin\RespondentMaster\CreateRespondentMasterUseCase;
use App\Application\Admin\RespondentMaster\ValidationException;
use App\Infrastructure\Database\RespondentMasterRepository;
use PHPUnit\Framework\TestCase;

class CreateRespondentMasterUseCaseTest extends TestCase
{
    private $repository;
    private $useCase;

    protected function setUp(): void
    {
        $this->repository = $this->createMock(RespondentMasterRepository::class);
        $this->useCase = new CreateRespondentMasterUseCase($this->repository);
    }

    public function testExecuteSuccess(): void
    {
        $data = [
            'master_code' => 'M001',
            'line_display_name' => 'User One',
            'name' => 'Name One',
            'email' => 'one@example.com',
            'honorific' => 'Mr.',
            'note' => 'Note One'
        ];

        $this->repository->expects($this->exactly(2))
            ->method('findBy')
            ->willReturnMap([
                [['master_code' => 'M001'], []],
                [['line_display_name' => 'User One'], []],
            ]);

        $this->repository->expects($this->once())
            ->method('save')
            ->with($data)
            ->willReturn(1);

        $id = $this->useCase->execute($data);
        $this->assertEquals(1, $id);
    }

    public function testExecuteWithZeroValues(): void
    {
        $data = [
            'master_code' => 'M001',
            'line_display_name' => 'User One',
            'name' => 'Name One',
            'email' => 'one@example.com',
            'honorific' => '0',
            'note' => '0'
        ];

        $this->repository->method('findBy')->willReturn([]);
        $this->repository->expects($this->once())
            ->method('save')
            ->with($this->callback(function ($d) {
                return $d['honorific'] === '0' && $d['note'] === '0';
            }))
            ->willReturn(1);

        $id = $this->useCase->execute($data);
        $this->assertEquals(1, $id);
    }

    public function testExecuteDuplicateMasterCode(): void
    {
        $data = [
            'master_code' => 'M001',
            'line_display_name' => 'User One',
            'name' => 'Name One',
            'email' => 'one@example.com'
        ];

        $this->repository->method('findBy')
            ->willReturnMap([
                [['master_code' => 'M001'], [['id' => 1]]],
                [['line_display_name' => 'User One'], []],
            ]);

        $this->expectException(ValidationException::class);
        $this->useCase->execute($data);
    }
}
