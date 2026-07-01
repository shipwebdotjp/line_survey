<?php

declare(strict_types=1);

namespace App\Application\Admin\Survey;

use App\Infrastructure\Database\ResponseRepository;
use App\Infrastructure\Database\SurveyRepository;
use Slim\Exception\HttpNotFoundException;
use Psr\Http\Message\ServerRequestInterface as Request;

class GetSurveySummaryUseCase
{
    private array $flattenedCache = [];

    public function __construct(
        private SurveyRepository $surveyRepository,
        private ResponseRepository $responseRepository
    ) {
    }

    public function execute(int $surveyId, int $ownerUserId, Request $request): array
    {
        $survey = $this->surveyRepository->findById($surveyId, $ownerUserId);
        if (!$survey) {
            throw new HttpNotFoundException($request, 'Survey not found');
        }

        $responses = $this->responseRepository->findBySurveyIdWithRespondent($surveyId);
        $totalResponses = count($responses);

        // 1. Collect all question names and their metadata with priority
        $questionsMetadata = [];
        $orderedNames = [];

        // Current survey definition (Highest Priority)
        $currentElements = $this->flattenElementsCached($survey['questions_json'] ?? []);
        foreach ($currentElements as $el) {
            $name = $el['name'] ?? null;
            if (!$name) continue;
            $name = (string)$name;
            if (!in_array($name, $orderedNames)) {
                $orderedNames[] = $name;
            }
            $questionsMetadata[$name] = $el;
        }

        // Snapshots from responses (Second Priority)
        foreach ($responses as $resp) {
            $snapshotElements = $this->flattenElementsCached($resp['survey_snapshot_json'] ?? []);
            foreach ($snapshotElements as $el) {
                $name = $el['name'] ?? null;
                if (!$name) continue;
                $name = (string)$name;
                if (!in_array($name, $orderedNames)) {
                    $orderedNames[] = $name;
                }
                if (!isset($questionsMetadata[$name])) {
                    $questionsMetadata[$name] = $el;
                } else {
                    $questionsMetadata[$name] = $this->mergeMetadata($questionsMetadata[$name], $el);
                }
            }
        }

        // Answers (Lowest Priority for metadata)
        foreach ($responses as $resp) {
            $answers = $resp['answer_json'] ?? [];
            if (!is_array($answers)) continue;
            foreach ($answers as $name => $value) {
                $name = (string)$name;
                if (!in_array($name, $orderedNames)) {
                    $orderedNames[] = $name;
                }
                if (!isset($questionsMetadata[$name])) {
                    $questionsMetadata[$name] = [
                        'name' => $name,
                        'title' => $name,
                        'type' => 'text'
                    ];
                }
            }
        }

        // 2. Prepare flattened snapshots for each response
        $responseSnapshots = [];
        foreach ($responses as $i => $resp) {
            $responseSnapshots[$i] = $this->flattenElementsCached($resp['survey_snapshot_json'] ?? []);
        }

        // 3. Aggregate results
        $questionsSummary = [];
        foreach ($orderedNames as $name) {
            $primaryMeta = $questionsMetadata[$name];
            $primaryType = $primaryMeta['type'] ?? 'unknown';
            $primaryCategory = $this->getTypeCategory($primaryType);

            $summary = [
                'name' => $name,
                'title' => $primaryMeta['title'] ?? $name,
                'type' => $primaryCategory === 'unsupported' ? 'unsupported' : $primaryType,
                'targetCount' => 0,
                'answeredCount' => 0,
                'emptyCount' => 0,
            ];

            $typeSpecificData = [
                'answers' => [],
                'counts' => []
            ];

            foreach ($responses as $i => $resp) {
                $elementsInSnapshot = $responseSnapshots[$i];
                $elInSnapshot = null;
                foreach ($elementsInSnapshot as $e) {
                    if ((string)($e['name'] ?? '') === $name) {
                        $elInSnapshot = $e;
                        break;
                    }
                }

                $answerValue = $resp['answer_json'][$name] ?? null;
                $existsInSnapshot = $elInSnapshot !== null;

                if ($existsInSnapshot) {
                    $summary['targetCount']++;
                }

                // Use the type from the snapshot if available, otherwise fallback to primary
                $actualType = $elInSnapshot['type'] ?? $primaryType;
                $actualCategory = $this->getTypeCategory($actualType);

                $isAnswered = $this->isAnswered($actualCategory, $answerValue);
                if ($actualCategory === 'boolean' && $existsInSnapshot) {
                    $isAnswered = true;
                }

                if ($isAnswered) {
                    $summary['answeredCount']++;
                }

                $this->aggregate($typeSpecificData, $actualCategory, $elInSnapshot ?? $primaryMeta, $answerValue, $existsInSnapshot);
            }

            $summary['emptyCount'] = max(0, $summary['targetCount'] - $summary['answeredCount']);
            $this->formatTypeSpecificData($summary, $primaryCategory, $typeSpecificData, $primaryMeta);

            $questionsSummary[] = $summary;
        }

        return [
            'totalResponses' => $totalResponses,
            'questions' => $questionsSummary
        ];
    }

    private function flattenElementsCached(?array $questionsJson): array
    {
        if ($questionsJson === null) return [];
        $key = md5(json_encode($questionsJson));
        if (isset($this->flattenedCache[$key])) {
            return $this->flattenedCache[$key];
        }
        return $this->flattenedCache[$key] = $this->flattenElements($questionsJson);
    }

    private function flattenElements(array $questionsJson): array
    {
        $elements = [];
        $pages = $questionsJson['pages'] ?? [];
        if (!is_array($pages)) return [];

        foreach ($pages as $page) {
            if (isset($page['elements']) && is_array($page['elements'])) {
                $this->collectElements($page['elements'], $elements);
            }
        }
        return $elements;
    }

    private function collectElements(array $source, array &$elements): void
    {
        foreach ($source as $el) {
            $type = $el['type'] ?? '';
            if ($type === 'panel') {
                if (isset($el['elements']) && is_array($el['elements'])) {
                    $this->collectElements($el['elements'], $elements);
                }
            } else {
                $elements[] = $el;
            }
        }
    }

    private function mergeMetadata(array $existing, array $new): array
    {
        $fields = ['title', 'type', 'choices', 'valueTrue', 'valueFalse'];
        foreach ($fields as $field) {
            if (!isset($existing[$field]) && isset($new[$field])) {
                $existing[$field] = $new[$field];
            }
        }
        return $existing;
    }

    private function getTypeCategory(string $type): string
    {
        switch ($type) {
            case 'text':
            case 'comment':
                return 'text';
            case 'boolean':
                return 'boolean';
            case 'checkbox':
                return 'checkbox';
            case 'radiogroup':
            case 'dropdown':
                return 'choice';
            default:
                return 'unsupported';
        }
    }

    private function isAnswered(string $category, $value): bool
    {
        if ($value === null) return false;
        if ($category === 'text') {
            return is_string($value) && trim($value) !== '';
        }
        if ($category === 'checkbox') {
            return is_array($value) && count($value) > 0;
        }
        if ($category === 'boolean') {
            return true;
        }
        if (is_string($value) && trim($value) === '') return false;
        return true;
    }

    private function aggregate(array &$data, string $category, array $el, $value, bool $existsInSnapshot): void
    {
        if (!$existsInSnapshot && $value === null) return;

        switch ($category) {
            case 'text':
                if (is_string($value) && trim($value) !== '') {
                    $data['answers'][] = $value;
                }
                break;
            case 'boolean':
                if ($existsInSnapshot) {
                    $key = ($value === null) ? ($el['valueFalse'] ?? false) : $value;
                    $keyStr = $this->stringify($key);
                    $data['counts'][$keyStr] = ($data['counts'][$keyStr] ?? 0) + 1;
                }
                break;
            case 'checkbox':
                $vals = is_array($value) ? $value : ($value !== null && $value !== '' ? [$value] : []);
                foreach ($vals as $v) {
                    $vStr = $this->stringify($v);
                    $data['counts'][$vStr] = ($data['counts'][$vStr] ?? 0) + 1;
                }
                break;
            case 'choice':
                if ($value !== null && $value !== '') {
                    $vStr = $this->stringify($value);
                    $data['counts'][$vStr] = ($data['counts'][$vStr] ?? 0) + 1;
                }
                break;
        }
    }

    private function formatTypeSpecificData(array &$summary, string $category, array $data, array $meta): void
    {
        switch ($category) {
            case 'text':
                $summary['answers'] = $data['answers'] ?? [];
                break;
            case 'boolean':
                $summary['choices'] = $this->formatBooleanChoices($data, $meta, $summary['targetCount']);
                break;
            case 'checkbox':
                $summary['choices'] = $this->formatChoices($data, $meta, $summary['targetCount'], 'checkbox');
                break;
            case 'choice':
                $summary['choices'] = $this->formatChoices($data, $meta, $summary['answeredCount'], 'choice');
                break;
        }
    }

    private function formatBooleanChoices(array $data, array $meta, int $total): array
    {
        $valTrue = $meta['valueTrue'] ?? true;
        $valFalse = $meta['valueFalse'] ?? false;

        $choices = [];
        foreach ([$valTrue, $valFalse] as $val) {
            $valStr = $this->stringify($val);
            $count = $data['counts'][$valStr] ?? 0;
            $choices[] = [
                'value' => $val,
                'label' => $this->getLabel($val),
                'count' => $count,
                'rate' => $total > 0 ? round(($count / $total) * 100, 1) : 0
            ];
        }
        return $choices;
    }

    private function formatChoices(array $data, array $meta, int $baseCount, string $category): array
    {
        $choicesMeta = $meta['choices'] ?? [];
        $formatted = [];
        $definedValues = [];

        foreach ($choicesMeta as $c) {
            if (is_array($c)) {
                $val = $c['value'];
                $label = $c['text'] ?? $val;
            } else {
                $val = $c;
                $label = $c;
            }
            $valStr = $this->stringify($val);
            $definedValues[] = $valStr;

            $count = $data['counts'][$valStr] ?? 0;
            $formatted[] = [
                'value' => $val,
                'label' => (string)$label,
                'count' => $count,
                'rate' => $baseCount > 0 ? round(($count / $baseCount) * 100, 1) : 0
            ];
        }

        // Add values that were in the data but not in metadata
        foreach ($data['counts'] ?? [] as $valStr => $count) {
            if (!in_array($valStr, $definedValues)) {
                $formatted[] = [
                    'value' => $valStr,
                    'label' => $valStr,
                    'count' => $count,
                    'rate' => $baseCount > 0 ? round(($count / $baseCount) * 100, 1) : 0
                ];
            }
        }

        return $formatted;
    }

    private function stringify($val): string
    {
        if ($val === true) return 'true';
        if ($val === false) return 'false';
        if ($val === null) return '';
        return (string)$val;
    }

    private function getLabel($val): string
    {
        if ($val === true) return 'true';
        if ($val === false) return 'false';
        if ($val === null) return '';
        return (string)$val;
    }
}
