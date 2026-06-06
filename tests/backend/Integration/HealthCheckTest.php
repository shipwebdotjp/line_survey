<?php

namespace Tests\Backend\Integration;

use PHPUnit\Framework\TestCase;
use Slim\Psr7\Factory\ServerRequestFactory;
use App\Config\Settings;

class HealthCheckTest extends TestCase
{
    private $app;

    protected function setUp(): void
    {
        $this->app = require __DIR__ . '/../../../backend/bootstrap/app.php';
    }

    public function testHealthCheckReturnsOk()
    {
        $request = (new ServerRequestFactory())->createServerRequest('GET', '/api/health');
        $response = $this->app->handle($request);

        $this->assertEquals(200, $response->getStatusCode());

        $payload = json_decode((string)$response->getBody(), true);
        $this->assertArrayHasKey('data', $payload);
        $this->assertEquals(['status' => 'ok'], $payload['data']);
    }

    public function testNotFoundReturnsErrorEnvelope()
    {
        $request = (new ServerRequestFactory())->createServerRequest('GET', '/api/notfound');
        $response = $this->app->handle($request);

        $this->assertEquals(404, $response->getStatusCode());

        $payload = json_decode((string)$response->getBody(), true);
        $this->assertArrayHasKey('error', $payload);
        $this->assertEquals('NOT_FOUND', $payload['error']['code']);
    }
}
