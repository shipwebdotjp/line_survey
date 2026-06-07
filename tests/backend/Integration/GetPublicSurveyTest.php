<?php

namespace Tests\Backend\Integration;

use PHPUnit\Framework\TestCase;
use Slim\Psr7\Factory\ServerRequestFactory;
use App\Infrastructure\Database\SurveyRepository;
use App\Infrastructure\Support\DateTimeHelper;
use Illuminate\Database\ConnectionInterface;

class GetPublicSurveyTest extends TestCase
{
    private $app;
    private $container;
    private $surveyRepository;
    private $db;

    protected function setUp(): void
    {
        $this->app = require __DIR__ . '/../../../backend/bootstrap/app.php';
        $this->container = $this->app->getContainer();
        $this->surveyRepository = $this->container->get(SurveyRepository::class);
        $this->db = $this->container->get(ConnectionInterface::class);

        // Clean up
        $this->db->statement('SET FOREIGN_KEY_CHECKS=0');
        $this->db->statement('TRUNCATE TABLE responses');
        $this->db->statement('TRUNCATE TABLE surveys');
        $this->db->statement('SET FOREIGN_KEY_CHECKS=1');
    }

    public function testGetPublicSurveySuccess()
    {
        $publicId = 'test-survey-published';
        $this->surveyRepository->save([
            'public_id' => $publicId,
            'title' => 'Test Survey',
            'description' => 'Test Description',
            'questions_json' => ['pages' => []],
            'status' => 'published',
            'allow_multiple' => false,
            'allow_edit' => false,
            'starts_at' => DateTimeHelper::nowTokyo()->modify('-1 day')->format('Y-m-d H:i:s'),
            'ends_at' => DateTimeHelper::nowTokyo()->modify('+1 day')->format('Y-m-d H:i:s'),
        ]);

        $request = (new ServerRequestFactory())->createServerRequest('GET', "/api/surveys/public/$publicId");
        $response = $this->app->handle($request);

        $this->assertEquals(200, $response->getStatusCode());
        $payload = json_decode((string)$response->getBody(), true);

        $this->assertTrue($payload['data']['can_answer']);
        $this->assertNull($payload['data']['reason']);
        $this->assertEquals('Test Survey', $payload['data']['survey']['title']);
        $this->assertIsArray($payload['data']['survey']['questions_json']);
    }

    public function testGetPublicSurveyNotPublished()
    {
        $publicId = 'test-survey-draft';
        $this->surveyRepository->save([
            'public_id' => $publicId,
            'title' => 'Draft Survey',
            'status' => 'draft',
            'questions_json' => [],
        ]);

        $request = (new ServerRequestFactory())->createServerRequest('GET', "/api/surveys/public/$publicId");
        $response = $this->app->handle($request);

        $this->assertEquals(200, $response->getStatusCode());
        $payload = json_decode((string)$response->getBody(), true);

        $this->assertFalse($payload['data']['can_answer']);
        $this->assertEquals('not_published', $payload['data']['reason']);
    }

    public function testGetPublicSurveyNotStarted()
    {
        $publicId = 'test-survey-not-started';
        $this->surveyRepository->save([
            'public_id' => $publicId,
            'title' => 'Future Survey',
            'status' => 'published',
            'questions_json' => [],
            'starts_at' => DateTimeHelper::nowTokyo()->modify('+1 day')->format('Y-m-d H:i:s'),
        ]);

        $request = (new ServerRequestFactory())->createServerRequest('GET', "/api/surveys/public/$publicId");
        $response = $this->app->handle($request);

        $this->assertEquals(200, $response->getStatusCode());
        $payload = json_decode((string)$response->getBody(), true);

        $this->assertFalse($payload['data']['can_answer']);
        $this->assertEquals('not_started', $payload['data']['reason']);
    }

    public function testGetPublicSurveyClosed()
    {
        $publicId = 'test-survey-closed';
        $this->surveyRepository->save([
            'public_id' => $publicId,
            'title' => 'Past Survey',
            'status' => 'published',
            'questions_json' => [],
            'ends_at' => DateTimeHelper::nowTokyo()->modify('-1 day')->format('Y-m-d H:i:s'),
        ]);

        $request = (new ServerRequestFactory())->createServerRequest('GET', "/api/surveys/public/$publicId");
        $response = $this->app->handle($request);

        $this->assertEquals(200, $response->getStatusCode());
        $payload = json_decode((string)$response->getBody(), true);

        $this->assertFalse($payload['data']['can_answer']);
        $this->assertEquals('closed', $payload['data']['reason']);
    }

    public function testGetPublicSurveyNotFound()
    {
        $request = (new ServerRequestFactory())->createServerRequest('GET', "/api/surveys/public/non-existent");
        $response = $this->app->handle($request);

        $this->assertEquals(404, $response->getStatusCode());
        $payload = json_decode((string)$response->getBody(), true);
        $this->assertEquals('NOT_FOUND', $payload['error']['code']);
    }
}
