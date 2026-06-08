<?php

namespace Tests\Backend\Integration\Admin\Survey;

use PHPUnit\Framework\TestCase;
use Slim\Psr7\Factory\ServerRequestFactory;
use App\Config\Settings;
use Illuminate\Database\ConnectionInterface;

class GetResponseActionTest extends TestCase
{
    private $app;
    private $db;
    private $user;
    private $pass;

    protected function setUp(): void
    {
        $this->app = require __DIR__ . '/../../../../../backend/bootstrap/app.php';
        $this->db = $this->app->getContainer()->get(ConnectionInterface::class);
        $this->db->beginTransaction();

        $settings = $this->app->getContainer()->get(Settings::class);
        $this->user = $settings->get('auth.admin_user');
        $this->pass = $settings->get('auth.admin_pass');
    }

    protected function tearDown(): void
    {
        $this->db->rollBack();
    }

    private function createRequest(string $method, string $path)
    {
        $request = (new ServerRequestFactory())->createServerRequest($method, $path);
        $request = $request->withHeader('Authorization', 'Basic ' . base64_encode($this->user . ':' . $this->pass));
        return $request;
    }

    public function testGetResponseSuccess()
    {
        $surveyId = $this->db->table('surveys')->insertGetId([
            'public_id' => 'test_survey',
            'title' => 'Test Survey',
            'questions_json' => json_encode(['pages' => []]),
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s')
        ]);

        $respondentId = $this->db->table('respondents')->insertGetId([
            'line_user_id' => 'u123',
            'line_display_name' => 'UserL',
            'name' => 'User Name',
            'email' => 'user@example.com',
            'honorific' => 'さん',
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s')
        ]);

        $responseId = $this->db->table('responses')->insertGetId([
            'survey_id' => $surveyId,
            'respondent_id' => $respondentId,
            'edit_token' => 'token123',
            'answer_json' => json_encode(['q1' => 'a1']),
            'survey_snapshot_json' => json_encode(['title' => 'Snapshot Title']),
            'submitted_at' => date('Y-m-d H:i:s'),
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s')
        ]);

        $request = $this->createRequest('GET', "/api/admin/surveys/$surveyId/responses/$responseId");
        $response = $this->app->handle($request);

        $this->assertEquals(200, $response->getStatusCode());
        $payload = json_decode((string)$response->getBody(), true);

        $this->assertEquals($responseId, $payload['data']['id']);
        $this->assertEquals(['q1' => 'a1'], $payload['data']['answer_json']);
        $this->assertEquals('User Name', $payload['data']['respondent']['name']);
        $this->assertEquals('Snapshot Title', $payload['data']['survey_snapshot_json']['title']);
    }

    public function testGetResponseNotFound()
    {
        $request = $this->createRequest('GET', "/api/admin/surveys/999/responses/999");
        $response = $this->app->handle($request);

        $this->assertEquals(404, $response->getStatusCode());
    }

    public function testGetResponseInvalidId()
    {
        $request = $this->createRequest('GET', "/api/admin/surveys/abc/responses/def");
        $response = $this->app->handle($request);

        $this->assertEquals(400, $response->getStatusCode());
        $payload = json_decode((string)$response->getBody(), true);
        $this->assertEquals('VALIDATION_ERROR', $payload['code']);
    }
}
