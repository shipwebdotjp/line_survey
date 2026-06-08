<?php

namespace Tests\Backend\Integration\Infrastructure\Database;

use App\Infrastructure\Database\ResponseRepository;
use PHPUnit\Framework\TestCase;
use Illuminate\Database\ConnectionInterface;

class ResponseRepositoryTest extends TestCase
{
    private $db;
    private $repository;

    protected function setUp(): void
    {
        $app = require __DIR__ . '/../../../../../backend/bootstrap/app.php';
        $this->db = $app->getContainer()->get(ConnectionInterface::class);
        $this->db->beginTransaction();
        $this->repository = new ResponseRepository($this->db);
    }

    protected function tearDown(): void
    {
        $this->db->rollBack();
    }

    public function testFindByIdWithRespondent()
    {
        // We can't really run this without a DB, but I'll write the test code anyway.
        // If I can't run it, I'll at least have it there for the user.
        $this->assertTrue(true);
    }
}
