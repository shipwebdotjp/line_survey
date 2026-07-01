<?php

declare(strict_types=1);

namespace Tests\Unit\Application\Admin\Respondent;

use App\Application\Admin\Respondent\GetRespondentUseCase;
use App\Infrastructure\Database\RespondentRepository;
use App\Infrastructure\Database\ResponseRepository;
use PHPUnit\Framework\TestCase;

class GetRespondentUseCaseTest extends TestCase
{
    private $respondentRepository;
    private $responseRepository;
    private $useCase;

    protected function setUp(): void
    {
        $this->respondentRepository = $this->createMock(RespondentRepository::class);
        $this->responseRepository = $this->createMock(ResponseRepository::class);
        $this->useCase = new GetRespondentUseCase($this->respondentRepository, $this->responseRepository);
    }

    public function testExecuteSuccess(): void
    {
        $id = 1;
        $ownerId = 10;
        $respondentData = ['id' => $id, 'name' => 'Test', 'owner_user_id' => $ownerId];

        $this->respondentRepository->expects($this->once())
            ->method('findById')
            ->with($id, $ownerId)
            ->willReturn($respondentData);

        $this->responseRepository->expects($this->once())
            ->method('findHistoryForAdmin')
            ->with($id)
            ->willReturn([]);

        $result = $this->useCase->execute($id, $ownerId);

        $this->assertNotNull($result);
        $this->assertEquals($id, $result['id']);
        $this->assertArrayHasKey('responses', $result);
    }

    public function testExecuteNotFound(): void
    {
        $id = 1;
        $ownerId = 10;

        $this->respondentRepository->expects($this->once())
            ->method('findById')
            ->with($id, $ownerId)
            ->willReturn(null);

        $result = $this->useCase->execute($id, $ownerId);

        $this->assertNull($result);
    }
}
