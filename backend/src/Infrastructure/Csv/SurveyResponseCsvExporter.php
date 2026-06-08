<?php

declare(strict_types=1);

namespace App\Infrastructure\Csv;

use App\Infrastructure\Support\DateTimeHelper;

class SurveyResponseCsvExporter
{
    private const BASE_HEADER_COUNT = 7;

    /**
     * @param array $survey The survey definition (used if no responses exist)
     * @param array[] $responses Array of responses (each joined with respondent data)
     * @return string
     */
    public function export(array $survey, array $responses): string
    {
        $headers = $this->buildHeaders($survey, $responses);

        $output = fopen('php://temp', 'r+');

        // Add UTF-8 BOM
        fwrite($output, "\xEF\xBB\xBF");

        // Write headers
        fputcsv($output, $headers);

        // Write data
        foreach ($responses as $response) {
            $row = $this->buildRow($response, $headers);
            fputcsv($output, $row);
        }

        rewind($output);
        $csv = stream_get_contents($output);
        fclose($output);

        // Ensure CRLF
        return str_replace("\n", "\r\n", str_replace("\r\n", "\n", $csv));
    }

    private function buildHeaders(array $survey, array $responses): array
    {
        $baseHeaders = [
            '回答ID',
            '初回回答日時',
            '最終更新日時',
            'LINE表示名',
            '氏名',
            '敬称',
            'メール'
        ];

        $questionColumns = [];
        if (empty($responses)) {
            // If no responses, use the current survey definition
            $questionColumns = $this->extractQuestionTitles($survey['questions_json'] ?? []);
        } else {
            // Build union of all question titles from snapshots in responses
            foreach ($responses as $response) {
                $snapshot = $response['survey_snapshot_json'] ?? [];
                $titles = $this->extractQuestionTitles($snapshot);
                foreach ($titles as $title) {
                    if (!in_array($title, $questionColumns, true)) {
                        $questionColumns[] = $title;
                    }
                }
            }
        }

        return array_merge($baseHeaders, $questionColumns);
    }

    private function extractQuestionTitles(array $questionsJson): array
    {
        $titles = [];
        // Handle SurveyJS structure (pages -> elements)
        $pages = $questionsJson['pages'] ?? [];
        foreach ($pages as $page) {
            $elements = $page['elements'] ?? [];
            foreach ($elements as $element) {
                // Use 'title' if available, otherwise 'name'
                $titles[] = $element['title'] ?? $element['name'] ?? '';
            }
        }
        return array_filter($titles, fn($t) => $t !== '');
    }

    private function buildRow(array $response, array $headers): array
    {
        $row = [
            $response['id'],
            DateTimeHelper::formatTokyo(DateTimeHelper::parseTokyo($response['submitted_at'])),
            DateTimeHelper::formatTokyo(DateTimeHelper::parseTokyo($response['updated_at'])),
            $response['respondent_line_display_name'] ?? '',
            $response['respondent_name'] ?? '',
            $response['respondent_honorific'] ?? '',
            $response['respondent_email'] ?? '',
        ];

        $answerJson = $response['answer_json'] ?? [];
        $snapshot = $response['survey_snapshot_json'] ?? [];

        // Create a mapping from title to name in the snapshot
        $titleToName = [];
        $pages = $snapshot['pages'] ?? [];
        foreach ($pages as $page) {
            $elements = $page['elements'] ?? [];
            foreach ($elements as $element) {
                $name = $element['name'] ?? null;
                $title = $element['title'] ?? $name;
                if ($title && $name) {
                    $titleToName[$title] = $name;
                }
            }
        }

        // Add answers for each question column
        for ($i = self::BASE_HEADER_COUNT; $i < count($headers); $i++) {
            $title = $headers[$i];
            $name = $titleToName[$title] ?? null;
            $value = ($name && isset($answerJson[$name])) ? $answerJson[$name] : '';

            if (is_array($value)) {
                $row[] = implode(';', $value);
            } else {
                $row[] = (string)$value;
            }
        }

        return $row;
    }
}
