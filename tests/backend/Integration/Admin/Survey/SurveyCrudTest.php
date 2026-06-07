<?php

namespace Tests\Backend\Integration\Admin\Survey;

use PHPUnit\Framework\TestCase;
use Slim\Psr7\Factory\ServerRequestFactory;
use App\Config\Settings;
use Illuminate\Database\ConnectionInterface;

class SurveyCrudTest extends TestCase
{
    private $app;
    private $db;

    protected function setUp(): void
    {
        $this->app = require __DIR__ . '/../../../../../backend/bootstrap/app.php';
        $this->db = $this->app->getContainer()->get(ConnectionInterface::class);
        $this->db->beginTransaction();

        // Setup Basic Auth
        $settings = $this->app->getContainer()->get(Settings::class);
        $this->user = $settings->get('auth.admin_user');
        $this->pass = $settings->get('auth.admin_pass');
    }

    protected function tearDown(): void
    {
        $this->db->rollBack();
    }

    private function createRequest(string $method, string $path, array $body = null)
    {
        $request = (new ServerRequestFactory())->createServerRequest($method, $path);
        $request = $request->withHeader('Authorization', 'Basic ' . base64_encode($this->user . ':' . $this->pass));

        if ($body !== null) {
            $request = $request->withHeader('Content-Type', 'application/json');
            $request->getBody()->write(json_encode($body));
        }

        return $request;
    }

    public function testListSurveys()
    {
        $request = $this->createRequest('GET', '/api/admin/surveys');
        $response = $this->app->handle($request);

        $this->assertEquals(200, $response->getStatusCode());
        $payload = json_decode((string)$response->getBody(), true);
        $this->assertArrayHasKey('data', $payload);
        $this->assertIsArray($payload['data']);
    }

    public function testCreateSurveySuccess()
    {
        $body = [
            'title' => 'Test Survey',
            'questions_json' => ['pages' => []],
            'status' => 'draft'
        ];
        $request = $this->createRequest('POST', '/api/admin/surveys', $body);
        $response = $this->app->handle($request);

        $this->assertEquals(201, $response->getStatusCode());
        $payload = json_decode((string)$response->getBody(), true);
        $this->assertArrayHasKey('id', $payload['data']);

        $id = $payload['data']['id'];
        $survey = $this->db->selectOne('SELECT * FROM surveys WHERE id = ?', [$id]);
        $this->assertEquals('Test Survey', $survey->title);
    }

    public function testCreateSurveyInvalidJson()
    {
        $body = [
            'title' => 'Test Survey',
            'questions_json' => '{invalid json',
            'status' => 'draft'
        ];
        $request = $this->createRequest('POST', '/api/admin/surveys', $body);
        $response = $this->app->handle($request);

        $this->assertEquals(400, $response->getStatusCode());
        $payload = json_decode((string)$response->getBody(), true);
        $this->assertEquals('VALIDATION_ERROR', $payload['code']);
    }

    public function testGetSurvey()
    {
        $id = $this->db->table('surveys')->insertGetId([
            'public_id' => 'test_id',
            'title' => 'Get Test',
            'questions_json' => json_encode(['pages' => []]),
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s')
        ]);

        $request = $this->createRequest('GET', '/api/admin/surveys/' . $id);
        $response = $this->app->handle($request);

        $this->assertEquals(200, $response->getStatusCode());
        $payload = json_decode((string)$response->getBody(), true);
        $this->assertEquals('Get Test', $payload['data']['title']);
        $this->assertArrayHasKey('response_count', $payload['data']);
    }

    public function testUpdateSurvey()
    {
        $id = $this->db->table('surveys')->insertGetId([
            'public_id' => 'test_id_upd',
            'title' => 'Old Title',
            'questions_json' => json_encode(['pages' => []]),
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s')
        ]);

        $body = ['title' => 'New Title'];
        $request = $this->createRequest('PUT', '/api/admin/surveys/' . $id, $body);
        $response = $this->app->handle($request);

        $this->assertEquals(200, $response->getStatusCode());

        $survey = $this->db->selectOne('SELECT * FROM surveys WHERE id = ?', [$id]);
        $this->assertEquals('New Title', $survey->title);
    }

    public function testDeleteSurveySuccess()
    {
        $id = $this->db->table('surveys')->insertGetId([
            'public_id' => 'test_id_del',
            'title' => 'Del Test',
            'questions_json' => json_encode(['pages' => []]),
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s')
        ]);

        $request = $this->createRequest('DELETE', '/api/admin/surveys/' . $id);
        $response = $this->app->handle($request);

        $this->assertEquals(200, $response->getStatusCode());

        $survey = $this->db->selectOne('SELECT * FROM surveys WHERE id = ?', [$id]);
        $this->assertNull($survey);
    }

    public function testDeleteSurveyConflict()
    {
        $survey_id = $this->db->table('surveys')->insertGetId([
            'public_id' => 'test_id_conf',
            'title' => 'Conflict Test',
            'questions_json' => json_encode(['pages' => []]),
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s')
        ]);

        $respondent_id = $this->db->table('respondents')->insertGetId([
            'line_user_id' => 'u123',
            'line_display_name' => 'User',
            'name' => 'User',
            'email' => 'user@example.com',
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s')
        ]);

        $this->db->table('responses')->insert([
            'survey_id' => $survey_id,
            'respondent_id' => $respondent_id,
            'edit_token' => 't123',
            'answer_json' => json_encode([]),
            'submitted_at' => date('Y-m-d H:i:s'),
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s')
        ]);

        $request = $this->createRequest('DELETE', '/api/admin/surveys/' . $survey_id);
        $response = $this->app->handle($request);

        $this->assertEquals(409, $response->getStatusCode());
        $payload = json_decode((string)$response->getBody(), true);
        $this->assertEquals('CONFLICT', $payload['code']);
    }

    public function testAuthRequired()
    {
        $request = (new ServerRequestFactory())->createServerRequest('GET', '/api/admin/surveys');
        $response = $this->app->handle($request);

        $this->assertEquals(401, $response->getStatusCode());
    }
}
