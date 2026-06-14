<?php

namespace Tests\Backend\Integration\Infrastructure\Database;

use App\Infrastructure\Database\ResponseDraftRepository;
use PHPUnit\Framework\TestCase;
use Illuminate\Database\ConnectionInterface;

class ResponseDraftRepositoryTest extends TestCase
{
    private $db;
    private $repository;

    protected function setUp(): void
    {
        // This will only work if the database is running and accessible
        try {
            $app = require __DIR__ . '/../../../../../backend/bootstrap/app.php';
            $this->db = $app->getContainer()->get(ConnectionInterface::class);
            $this->db->beginTransaction();
            $this->repository = new ResponseDraftRepository($this->db);
        } catch (\Exception $e) {
            $this->markTestSkipped('Database connection failed: ' . $e->getMessage());
        }
    }

    protected function tearDown(): void
    {
        if ($this->db) {
            $this->db->rollBack();
        }
    }

    public function testSaveAndFind()
    {
        $surveyId = 1;
        $respondentId = 1;
        $answerJson = ['question1' => 'answer1', 'question2' => [1, 2, 3]];

        // 1. Save new draft
        $data = [
            'survey_id' => $surveyId,
            'respondent_id' => $respondentId,
            'answer_json' => $answerJson,
        ];
        $id = $this->repository->save($data);
        $this->assertGreaterThan(0, $id);

        // 2. Find it
        $found = $this->repository->findBySurveyAndRespondent($surveyId, $respondentId);
        $this->assertNotNull($found);
        $this->assertEquals($surveyId, $found['survey_id']);
        $this->assertEquals($respondentId, $found['respondent_id']);
        $this->assertEquals($answerJson, $found['answer_json']);
        $this->assertArrayHasKey('created_at', $found);
        $this->assertArrayHasKey('updated_at', $found);

        // 3. Update it
        $newAnswerJson = ['question1' => 'updated_answer'];
        $updated = $this->repository->updateBySurveyAndRespondent($surveyId, $respondentId, [
            'answer_json' => $newAnswerJson,
        ]);
        $this->assertTrue($updated);

        $foundUpdated = $this->repository->findBySurveyAndRespondent($surveyId, $respondentId);
        $this->assertEquals($newAnswerJson, $foundUpdated['answer_json']);

        // 4. Delete it
        $deleted = $this->repository->deleteBySurveyAndRespondent($surveyId, $respondentId);
        $this->assertTrue($deleted);

        $foundDeleted = $this->repository->findBySurveyAndRespondent($surveyId, $respondentId);
        $this->assertNull($foundDeleted);
    }
}
