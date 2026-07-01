<?php

declare(strict_types=1);

namespace Tests\Unit\Application\Admin\Survey;

use App\Application\Admin\Survey\GetSurveySummaryUseCase;
use App\Infrastructure\Database\ResponseRepository;
use App\Infrastructure\Database\SurveyRepository;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ServerRequestInterface;

class GetSurveySummaryUseCaseTest extends TestCase
{
    private $surveyRepository;
    private $responseRepository;
    private $request;
    private $useCase;

    protected function setUp(): void
    {
        $this->surveyRepository = $this->createMock(SurveyRepository::class);
        $this->responseRepository = $this->createMock(ResponseRepository::class);
        $this->request = $this->createMock(ServerRequestInterface::class);
        $this->useCase = new GetSurveySummaryUseCase($this->surveyRepository, $this->responseRepository);
    }

    public function testExecuteAggregatesCorrectly(): void
    {
        $surveyId = 1;
        $ownerUserId = 123;
        $survey = [
            'id' => $surveyId,
            'questions_json' => [
                'pages' => [
                    [
                        'elements' => [
                            ['type' => 'text', 'name' => 'q1', 'title' => 'Question 1'],
                            [
                                'type' => 'boolean',
                                'name' => 'q2',
                                'title' => 'Question 2',
                                'valueTrue' => 'Yes',
                                'valueFalse' => 'No'
                            ],
                            [
                                'type' => 'radiogroup',
                                'name' => 'q3',
                                'title' => 'Question 3',
                                'choices' => ['A', 'B']
                            ],
                            [
                                'type' => 'checkbox',
                                'name' => 'q4',
                                'title' => 'Question 4',
                                'choices' => ['C', 'D']
                            ]
                        ]
                    ]
                ]
            ]
        ];

        $responses = [
            [
                'answer_json' => ['q1' => 'Answer 1', 'q2' => 'Yes', 'q3' => 'A', 'q4' => ['C', 'D']],
                'survey_snapshot_json' => $survey['questions_json']
            ],
            [
                'answer_json' => ['q2' => 'No', 'q3' => 'A', 'q4' => ['C']],
                'survey_snapshot_json' => $survey['questions_json']
            ],
            [
                'answer_json' => ['q3' => 'B'],
                'survey_snapshot_json' => $survey['questions_json']
            ]
        ];

        $this->surveyRepository->expects($this->once())
            ->method('findById')
            ->with($surveyId, $ownerUserId)
            ->willReturn($survey);

        $this->responseRepository->expects($this->once())
            ->method('findBySurveyIdWithRespondent')
            ->with($surveyId)
            ->willReturn($responses);

        $result = $this->useCase->execute($surveyId, $ownerUserId, $this->request);

        $this->assertEquals(3, $result['totalResponses']);
        $this->assertCount(4, $result['questions']);

        // q1 (text)
        $q1 = $result['questions'][0];
        $this->assertEquals('q1', $q1['name']);
        $this->assertEquals(3, $q1['targetCount']);
        $this->assertEquals(1, $q1['answeredCount']);
        $this->assertEquals(['Answer 1'], $q1['answers']);

        // q2 (boolean)
        $q2 = $result['questions'][1];
        $this->assertEquals('q2', $q2['name']);
        $this->assertEquals(3, $q2['targetCount']);
        $this->assertEquals(3, $q2['answeredCount']); // boolean always answered if in snapshot
        $this->assertCount(2, $q2['choices']);
        $this->assertEquals('Yes', $q2['choices'][0]['value']);
        $this->assertEquals(1, $q2['choices'][0]['count']);
        $this->assertEquals(33.3, $q2['choices'][0]['rate']);
        $this->assertEquals('No', $q2['choices'][1]['value']);
        $this->assertEquals(2, $q2['choices'][1]['count']); // 1 explicit 'No' + 1 missing treated as false
        $this->assertEquals(66.7, $q2['choices'][1]['rate']);

        // q3 (radiogroup)
        $q3 = $result['questions'][2];
        $this->assertEquals('q3', $q3['name']);
        $this->assertEquals(3, $q3['targetCount']);
        $this->assertEquals(3, $q3['answeredCount']);
        $this->assertCount(2, $q3['choices']);
        $this->assertEquals('A', $q3['choices'][0]['value']);
        $this->assertEquals(2, $q3['choices'][0]['count']);
        $this->assertEquals(66.7, $q3['choices'][0]['rate']);

        // q4 (checkbox)
        $q4 = $result['questions'][3];
        $this->assertEquals('q4', $q4['name']);
        $this->assertEquals(3, $q4['targetCount']);
        $this->assertEquals(2, $q4['answeredCount']);
        $this->assertCount(2, $q4['choices']);
        $this->assertEquals('C', $q4['choices'][0]['value']);
        $this->assertEquals(2, $q4['choices'][0]['count']);
        $this->assertEquals(66.7, $q4['choices'][0]['rate']);
    }

    public function testExecuteHandlesDeletedQuestions(): void
    {
        $surveyId = 1;
        $ownerUserId = 123;
        $survey = [
            'id' => $surveyId,
            'questions_json' => [
                'pages' => [
                    [
                        'elements' => [
                            ['type' => 'text', 'name' => 'q_new', 'title' => 'New Question'],
                        ]
                    ]
                ]
            ]
        ];

        $responses = [
            [
                'answer_json' => ['q_old' => 'Old Answer'],
                'survey_snapshot_json' => [
                    'pages' => [
                        [
                            'elements' => [
                                ['type' => 'text', 'name' => 'q_old', 'title' => 'Old Question'],
                            ]
                        ]
                    ]
                ]
            ]
        ];

        $this->surveyRepository->expects($this->once())
            ->method('findById')
            ->with($surveyId, $ownerUserId)
            ->willReturn($survey);

        $this->responseRepository->expects($this->once())
            ->method('findBySurveyIdWithRespondent')
            ->with($surveyId)
            ->willReturn($responses);

        $result = $this->useCase->execute($surveyId, $ownerUserId, $this->request);

        $this->assertCount(2, $result['questions']);
        $this->assertEquals('q_new', $result['questions'][0]['name']);
        $this->assertEquals('q_old', $result['questions'][1]['name']);

        $this->assertEquals(0, $result['questions'][0]['targetCount']); // q_new not in any snapshot
        $this->assertEquals(0, $result['questions'][0]['answeredCount']);

        $this->assertEquals(1, $result['questions'][1]['targetCount']);
        $this->assertEquals(1, $result['questions'][1]['answeredCount']);
    }

    public function testExecuteHandlesNullSnapshotByTreatingAsEmpty(): void
    {
        $surveyId = 1;
        $ownerUserId = 123;
        $survey = [
            'id' => $surveyId,
            'questions_json' => [
                'pages' => [
                    [
                        'elements' => [
                            ['type' => 'text', 'name' => 'q1', 'title' => 'Question 1'],
                        ]
                    ]
                ]
            ]
        ];

        $responses = [
            [
                'answer_json' => ['q1' => 'Answer 1'],
                'survey_snapshot_json' => null // Legacy
            ]
        ];

        $this->surveyRepository->expects($this->once())
            ->method('findById')
            ->with($surveyId, $ownerUserId)
            ->willReturn($survey);

        $this->responseRepository->expects($this->once())
            ->method('findBySurveyIdWithRespondent')
            ->with($surveyId)
            ->willReturn($responses);

        $result = $this->useCase->execute($surveyId, $ownerUserId, $this->request);

        $this->assertCount(1, $result['questions']);
        $this->assertEquals('q1', $result['questions'][0]['name']);
        // Since snapshot is null, it doesn't count as target in current implementation
        $this->assertEquals(0, $result['questions'][0]['targetCount']);
        $this->assertEquals(1, $result['questions'][0]['answeredCount']);
    }

    public function testExecuteHandlesDefaultBooleanValues(): void
    {
        $surveyId = 1;
        $ownerUserId = 123;
        $survey = [
            'id' => $surveyId,
            'questions_json' => [
                'pages' => [
                    [
                        'elements' => [
                            ['type' => 'boolean', 'name' => 'q_bool', 'title' => 'Boolean Question'],
                        ]
                    ]
                ]
            ]
        ];

        $responses = [
            [
                'answer_json' => ['q_bool' => true],
                'survey_snapshot_json' => $survey['questions_json']
            ],
            [
                'answer_json' => ['q_bool' => false],
                'survey_snapshot_json' => $survey['questions_json']
            ],
            [
                'answer_json' => [], // Missing value should be treated as false
                'survey_snapshot_json' => $survey['questions_json']
            ]
        ];

        $this->surveyRepository->expects($this->once())
            ->method('findById')
            ->with($surveyId, $ownerUserId)
            ->willReturn($survey);

        $this->responseRepository->expects($this->once())
            ->method('findBySurveyIdWithRespondent')
            ->with($surveyId)
            ->willReturn($responses);

        $result = $this->useCase->execute($surveyId, $ownerUserId, $this->request);

        $q = $result['questions'][0];
        $this->assertEquals('q_bool', $q['name']);
        $this->assertEquals(3, $q['targetCount']);
        $this->assertEquals(3, $q['answeredCount']);
        $this->assertCount(2, $q['choices']);

        // true choice
        $this->assertEquals(true, $q['choices'][0]['value']);
        $this->assertEquals(1, $q['choices'][0]['count']);

        // false choice
        $this->assertEquals(false, $q['choices'][1]['value']);
        $this->assertEquals(2, $q['choices'][1]['count']); // 1 explicit false + 1 missing
    }
}
