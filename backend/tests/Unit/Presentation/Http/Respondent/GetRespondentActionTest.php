<?php

declare(strict_types=1);

namespace Tests\Unit\Presentation\Http\Respondent;

use App\Presentation\Http\Respondent\GetRespondentAction;
use PHPUnit\Framework\TestCase;
use Slim\Psr7\Factory\ResponseFactory;
use Slim\Psr7\Factory\ServerRequestFactory;

class GetRespondentActionTest extends TestCase
{
    private GetRespondentAction $action;

    protected function setUp(): void
    {
        $this->action = new GetRespondentAction();
    }

    public function testInvokeReturnsRespondentData(): void
    {
        $request = (new ServerRequestFactory())->createServerRequest('GET', '/api/respondent');
        $respondent = [
            'id' => 123,
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'honorific' => '様',
            'line_display_name' => 'JohnD',
            'other_field' => 'hidden'
        ];
        $request = $request->withAttribute('respondent', $respondent);
        $response = (new ResponseFactory())->createResponse();

        $result = ($this->action)($request, $response);

        $this->assertEquals(200, $result->getStatusCode());
        $body = json_decode((string)$result->getBody(), true);
        $this->assertEquals(123, $body['data']['id']);
        $this->assertEquals('John Doe', $body['data']['name']);
        $this->assertEquals('john@example.com', $body['data']['email']);
        $this->assertEquals('様', $body['data']['honorific']);
        $this->assertEquals('JohnD', $body['data']['line_display_name']);
        $this->assertArrayNotHasKey('other_field', $body['data']);
    }
}
