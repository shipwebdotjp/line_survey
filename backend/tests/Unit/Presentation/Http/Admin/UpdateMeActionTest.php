<?php

declare(strict_types=1);

namespace Tests\Unit\Presentation\Http\Admin;

use App\Infrastructure\Database\UserRepository;
use App\Presentation\Http\Admin\UpdateMeAction;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ServerRequestInterface;
use Slim\Psr7\Factory\ResponseFactory;
use Illuminate\Database\ConnectionInterface;

class UpdateMeActionTest extends TestCase
{
    private $userRepository;
    private $db;
    private $action;
    private $request;
    private $response;

    protected function setUp(): void
    {
        $this->userRepository = $this->createMock(UserRepository::class);
        $this->db = $this->createMock(ConnectionInterface::class);
        $this->action = new UpdateMeAction($this->userRepository, $this->db);
        $this->request = $this->createMock(ServerRequestInterface::class);
        $this->response = (new ResponseFactory())->createResponse();
    }

    public function testInvokeUpdatesEmailAndReturnsUser(): void
    {
        $ownerUser = [
            'id' => 1,
            'line_user_id' => 'U123456',
            'line_display_name' => 'Admin User',
            'line_picture_url' => 'http://example.com/pic.jpg',
            'email' => 'old@example.com',
            'role' => 'admin',
            'created_at' => '2023-01-01 00:00:00',
            'updated_at' => '2023-01-01 00:00:00',
        ];

        $newEmail = 'new@example.com';
        $updatedUser = array_merge($ownerUser, ['email' => $newEmail, 'updated_at' => '2023-01-02 00:00:00']);

        $this->request->method('getAttribute')->with('owner_user')->willReturn($ownerUser);
        $this->request->method('getParsedBody')->willReturn(['email' => '  ' . $newEmail . '  ']);

        // Check uniqueness mock
        $this->db->method('selectOne')->willReturn(null);

        $this->userRepository->expects($this->once())
            ->method('update')
            ->with(1, ['email' => $newEmail])
            ->willReturn(true);

        $this->userRepository->expects($this->once())
            ->method('findById')
            ->with(1)
            ->willReturn($updatedUser);

        $result = ($this->action)($this->request, $this->response);

        $this->assertEquals(200, $result->getStatusCode());
        $payload = json_decode((string)$result->getBody(), true);
        $this->assertEquals($newEmail, $payload['data']['user']['email']);
    }

    public function testInvokeReturns422WhenEmailIsEmpty(): void
    {
        $ownerUser = ['id' => 1];
        $this->request->method('getAttribute')->with('owner_user')->willReturn($ownerUser);
        $this->request->method('getParsedBody')->willReturn(['email' => '  ']);

        $result = ($this->action)($this->request, $this->response);

        $this->assertEquals(422, $result->getStatusCode());
        $payload = json_decode((string)$result->getBody(), true);
        $this->assertEquals('VALIDATION_ERROR', $payload['code']);
        $this->assertEquals('メールアドレスは必須です。', $payload['error']);
    }

    public function testInvokeReturns422WhenEmailIsInvalid(): void
    {
        $ownerUser = ['id' => 1];
        $this->request->method('getAttribute')->with('owner_user')->willReturn($ownerUser);
        $this->request->method('getParsedBody')->willReturn(['email' => 'not-an-email']);

        $result = ($this->action)($this->request, $this->response);

        $this->assertEquals(422, $result->getStatusCode());
        $payload = json_decode((string)$result->getBody(), true);
        $this->assertEquals('VALIDATION_ERROR', $payload['code']);
        $this->assertEquals('正当なメールアドレス形式で入力してください。', $payload['error']);
    }

    public function testInvokeReturns422WhenEmailIsNotUnique(): void
    {
        $ownerUser = ['id' => 1];
        $this->request->method('getAttribute')->with('owner_user')->willReturn($ownerUser);
        $this->request->method('getParsedBody')->willReturn(['email' => 'taken@example.com']);

        // Mock email already taken by another user
        $this->db->method('selectOne')->willReturn(['id' => 2]);

        $result = ($this->action)($this->request, $this->response);

        $this->assertEquals(422, $result->getStatusCode());
        $payload = json_decode((string)$result->getBody(), true);
        $this->assertEquals('VALIDATION_ERROR', $payload['code']);
        $this->assertEquals('このメールアドレスは既に使用されています。', $payload['error']);
    }
}
