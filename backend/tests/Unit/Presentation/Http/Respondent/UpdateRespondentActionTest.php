<?php

declare(strict_types=1);

namespace Tests\Unit\Presentation\Http\Respondent;

use App\Infrastructure\Database\RespondentRepository;
use App\Presentation\Http\Respondent\UpdateRespondentAction;
use PHPUnit\Framework\TestCase;
use Slim\Psr7\Factory\ResponseFactory;
use Slim\Psr7\Factory\ServerRequestFactory;

class UpdateRespondentActionTest extends TestCase
{
    private $respondentRepository;
    private UpdateRespondentAction $action;

    protected function setUp(): void
    {
        $this->respondentRepository = $this->createMock(RespondentRepository::class);
        $this->action = new UpdateRespondentAction($this->respondentRepository);
    }

    public function testInvokeUpdatesRespondentAndReturnsData(): void
    {
        $respondent = [
            'id' => 123,
            'name' => 'Old Name',
            'email' => 'old@example.com',
            'line_display_name' => 'JohnD'
        ];

        $request = (new ServerRequestFactory())->createServerRequest('PUT', '/api/respondent');
        $request = $request->withAttribute('respondent', $respondent);
        $request = $request->withParsedBody([
            'name' => 'New Name',
            'email' => 'new@example.com'
        ]);
        $response = (new ResponseFactory())->createResponse();

        $this->respondentRepository->expects($this->once())
            ->method('update')
            ->with(123, ['name' => 'New Name', 'email' => 'new@example.com']);

        $this->respondentRepository->expects($this->once())
            ->method('findById')
            ->with(123)
            ->willReturn([
                'id' => 123,
                'name' => 'New Name',
                'email' => 'new@example.com',
                'line_display_name' => 'JohnD'
            ]);

        $result = ($this->action)($request, $response);

        $this->assertEquals(200, $result->getStatusCode());
        $body = json_decode((string)$result->getBody(), true);
        $this->assertEquals('New Name', $body['data']['name']);
        $this->assertEquals('new@example.com', $body['data']['email']);
    }

    public function testInvokeReturnsValidationErrorOnEmptyFields(): void
    {
        $respondent = ['id' => 123];
        $request = (new ServerRequestFactory())->createServerRequest('PUT', '/api/respondent')
            ->withAttribute('respondent', $respondent)
            ->withParsedBody(['name' => '', 'email' => '']);
        $response = (new ResponseFactory())->createResponse();

        $result = ($this->action)($request, $response);

        $this->assertEquals(400, $result->getStatusCode());
        $body = json_decode((string)$result->getBody(), true);
        $this->assertEquals('VALIDATION_ERROR', $body['code']);
        $this->assertArrayHasKey('name', $body['details']);
        $this->assertArrayHasKey('email', $body['details']);
    }

    public function testInvokeReturnsValidationErrorOnInvalidEmail(): void
    {
        $respondent = ['id' => 123];
        $request = (new ServerRequestFactory())->createServerRequest('PUT', '/api/respondent')
            ->withAttribute('respondent', $respondent)
            ->withParsedBody(['name' => 'Valid Name', 'email' => 'invalid-email']);
        $response = (new ResponseFactory())->createResponse();

        $result = ($this->action)($request, $response);

        $this->assertEquals(400, $result->getStatusCode());
        $body = json_decode((string)$result->getBody(), true);
        $this->assertEquals('VALIDATION_ERROR', $body['code']);
        $this->assertArrayHasKey('email', $body['details']);
    }
}
