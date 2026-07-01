<?php

declare(strict_types=1);

namespace Tests\Unit\Infrastructure\Database;

use App\Infrastructure\Database\RespondentRepository;
use Illuminate\Database\ConnectionInterface;
use PHPUnit\Framework\TestCase;

class RespondentRepositoryTest extends TestCase
{
    private $db;
    private $repository;

    protected function setUp(): void
    {
        $this->db = $this->createMock(ConnectionInterface::class);
        $this->repository = new RespondentRepository($this->db);
    }

    public function testFindByIdWithNoOwner(): void
    {
        $id = 1;
        $this->db->expects($this->once())
            ->method('selectOne')
            ->with($this->stringContains('WHERE id = ?'), [$id])
            ->willReturn((object)['id' => $id, 'name' => 'Test']);

        $result = $this->repository->findById($id);
        $this->assertEquals($id, $result['id']);
    }

    public function testFindByIdWithOwner(): void
    {
        $id = 1;
        $ownerId = 10;
        $this->db->expects($this->once())
            ->method('selectOne')
            ->with($this->stringContains('WHERE id = ? AND owner_user_id = ?'), [$id, $ownerId])
            ->willReturn((object)['id' => $id, 'owner_user_id' => $ownerId]);

        $result = $this->repository->findById($id, $ownerId);
        $this->assertEquals($id, $result['id']);
        $this->assertEquals($ownerId, $result['owner_user_id']);
    }

    public function testFindAllWithSummaryWithOwner(): void
    {
        $ownerId = 10;
        $this->db->expects($this->once())
            ->method('select')
            ->with($this->stringContains('WHERE r.owner_user_id = ?'), [$ownerId])
            ->willReturn([(object)['id' => 1, 'owner_user_id' => $ownerId]]);

        $results = $this->repository->findAllWithSummary($ownerId);
        $this->assertCount(1, $results);
        $this->assertEquals($ownerId, $results[0]['owner_user_id']);
    }

    public function testUpdateWithOwner(): void
    {
        $id = 1;
        $ownerId = 10;
        $data = ['name' => 'Updated'];

        $this->db->expects($this->once())
            ->method('update')
            ->with($this->stringContains('WHERE id = ? AND owner_user_id = ?'), $this->callback(function($bindings) use ($id, $ownerId) {
                return end($bindings) === $ownerId && prev($bindings) === $id;
            }))
            ->willReturn(1);

        $success = $this->repository->update($id, $data, $ownerId);
        $this->assertTrue($success);
    }

    public function testDeleteWithOwner(): void
    {
        $id = 1;
        $ownerId = 10;

        $this->db->expects($this->once())
            ->method('delete')
            ->with($this->stringContains('WHERE id = ? AND owner_user_id = ?'), [$id, $ownerId])
            ->willReturn(1);

        $success = $this->repository->delete($id, $ownerId);
        $this->assertTrue($success);
    }
}
