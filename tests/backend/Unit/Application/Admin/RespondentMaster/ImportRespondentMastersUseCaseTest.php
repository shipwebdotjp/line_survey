<?php

declare(strict_types=1);

namespace Tests\Unit\Application\Admin\RespondentMaster;

use App\Application\Admin\RespondentMaster\ImportRespondentMastersUseCase;
use App\Infrastructure\Database\RespondentMasterRepository;
use PHPUnit\Framework\TestCase;

class ImportRespondentMastersUseCaseTest extends TestCase
{
    private $repository;
    private $useCase;

    protected function setUp(): void
    {
        $this->repository = $this->createMock(RespondentMasterRepository::class);
        $this->useCase = new ImportRespondentMastersUseCase($this->repository);
    }

    public function testExecuteSuccess()
    {
        $csv = "master_code,line_display_name,name,honorific,email,note\n"
             . "M001,John Doe,John Doe,Mr.,john@example.com,Note 1\n"
             . "M002,Jane Smith,Jane Smith,Ms.,jane@example.com,Note 2";

        $this->repository->expects($this->atLeastOnce())
            ->method('findBy')
            ->willReturn([]);

        $this->repository->expects($this->exactly(2))
            ->method('save');

        $result = $this->useCase->execute($csv);

        $this->assertEquals(2, $result['imported']);
        $this->assertEmpty($result['errors']);
    }

    public function testExecuteUpsert()
    {
        $csv = "master_code,line_display_name,name,honorific,email,note\n"
             . "M001,John Updated,John Doe,Mr.,john@example.com,Note 1";

        $this->repository->expects($this->atLeastOnce())
            ->method('findBy')
            ->willReturn([
                ['id' => 1, 'master_code' => 'M001', 'line_display_name' => 'John Doe']
            ]);

        $this->repository->expects($this->once())
            ->method('update')
            ->with(1, $this->callback(function ($data) {
                return $data['line_display_name'] === 'John Updated';
            }));

        $result = $this->useCase->execute($csv);

        $this->assertEquals(1, $result['imported']);
        $this->assertEmpty($result['errors']);
    }

    public function testExecuteValidationErrors()
    {
        $csv = "master_code,line_display_name,name,honorific,email,note\n"
             . ",John Doe,John Doe,Mr.,john@example.com,Note 1\n" // empty master_code
             . "M002,Jane Smith,Jane Smith,Ms.,invalid-email,Note 2\n" // invalid email
             . "M003,Duplicate Name,Jane Smith,Ms.,jane@example.com,Note 3"; // duplicate name

        $this->repository->expects($this->atLeastOnce())
            ->method('findBy')
            ->willReturn([
                ['id' => 1, 'master_code' => 'M001', 'line_display_name' => 'Duplicate Name']
            ]);

        $result = $this->useCase->execute($csv);

        $this->assertEquals(0, $result['imported']);
        $this->assertCount(3, $result['errors']);
        $this->assertEquals('master_code is required.', $result['errors'][0]['reason']);
        $this->assertEquals('Invalid email format.', $result['errors'][1]['reason']);
        $this->assertEquals('line_display_name "Duplicate Name" is already used by another master_code.', $result['errors'][2]['reason']);
    }
}
