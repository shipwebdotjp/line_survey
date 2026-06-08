<?php

declare(strict_types=1);

namespace Tests\Unit\Infrastructure\Csv;

use App\Infrastructure\Csv\SurveyResponseCsvExporter;
use PHPUnit\Framework\TestCase;

class SurveyResponseCsvExporterTest extends TestCase
{
    private $exporter;

    protected function setUp(): void
    {
        $this->exporter = new SurveyResponseCsvExporter();
    }

    public function testExportWithNoResponsesUsesCurrentSurvey(): void
    {
        $survey = [
            'questions_json' => [
                'pages' => [
                    [
                        'elements' => [
                            ['name' => 'q1', 'title' => 'Question 1'],
                            ['name' => 'q2']
                        ]
                    ]
                ]
            ]
        ];
        $responses = [];

        $csv = $this->exporter->export($survey, $responses);

        // UTF-8 BOM
        $this->assertStringStartsWith("\xEF\xBB\xBF", $csv);

        // Header
        $lines = explode("\r\n", $csv);
        $header = str_getcsv(substr($lines[0], 3)); // skip BOM
        $this->assertEquals(['回答ID', '初回回答日時', '最終更新日時', 'LINE表示名', '氏名', '敬称', 'メール', 'Question 1', 'q2'], $header);
    }

    public function testExportWithResponsesUsesSnapshots(): void
    {
        $survey = []; // Should not be used
        $responses = [
            [
                'id' => 1,
                'submitted_at' => '2023-10-01 10:00:00',
                'updated_at' => '2023-10-01 10:00:00',
                'respondent_line_display_name' => 'User1',
                'respondent_name' => 'Name1',
                'respondent_honorific' => 'Sama',
                'respondent_email' => 'user1@example.com',
                'answer_json' => ['q1' => 'Ans1', 'q2' => ['A', 'B']],
                'survey_snapshot_json' => [
                    'pages' => [
                        [
                            'elements' => [
                                ['name' => 'q1', 'title' => 'Question 1'],
                                ['name' => 'q2', 'title' => 'Question 2']
                            ]
                        ]
                    ]
                ]
            ]
        ];

        $csv = $this->exporter->export($survey, $responses);

        $lines = explode("\r\n", $csv);
        $header = str_getcsv(substr($lines[0], 3)); // skip BOM
        $this->assertContains('Question 1', $header);
        $this->assertContains('Question 2', $header);

        $row1 = str_getcsv($lines[1]);
        $this->assertEquals('1', $row1[0]);
        $this->assertEquals('User1', $row1[3]);
        $this->assertEquals('Ans1', $row1[7]);
        $this->assertEquals('A;B', $row1[8]);
    }

    public function testExportHandlesUnionOfSnapshots(): void
    {
        $survey = [];
        $responses = [
            [
                'id' => 1,
                'submitted_at' => '2023-10-01 10:00:00',
                'updated_at' => '2023-10-01 10:00:00',
                'answer_json' => ['q1' => 'Ans1'],
                'survey_snapshot_json' => [
                    'pages' => [['elements' => [['name' => 'q1', 'title' => 'Q1']]]]
                ]
            ],
            [
                'id' => 2,
                'submitted_at' => '2023-10-01 11:00:00',
                'updated_at' => '2023-10-01 11:00:00',
                'answer_json' => ['q2' => 'Ans2'],
                'survey_snapshot_json' => [
                    'pages' => [['elements' => [['name' => 'q2', 'title' => 'Q2']]]]
                ]
            ]
        ];

        $csv = $this->exporter->export($survey, $responses);
        $lines = explode("\r\n", $csv);
        $header = str_getcsv(substr($lines[0], 3));

        $this->assertContains('Q1', $header);
        $this->assertContains('Q2', $header);

        $row1 = str_getcsv($lines[1]);
        $this->assertEquals('Ans1', $row1[7]); // Q1
        $this->assertEquals('', $row1[8]);     // Q2

        $row2 = str_getcsv($lines[2]);
        $this->assertEquals('', $row2[7]);     // Q1
        $this->assertEquals('Ans2', $row2[8]); // Q2
    }
}
