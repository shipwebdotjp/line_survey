<?php

declare(strict_types=1);

namespace App\Presentation\Http\Admin\Survey;

use App\Presentation\Http\JsonResponse;
use Psr\Http\Message\ResponseInterface as Response;

trait QuestionsJsonValidatorTrait
{
    /**
     * @param array $data Reference to the request data array
     * @param Response $response
     * @return Response|null Returns Response on error, null on success
     */
    protected function validateAndDecodeQuestionsJson(array &$data, Response $response): ?Response
    {
        if (!isset($data['questions_json'])) {
            return null;
        }

        $input = $data['questions_json'];
        $questions = null;

        if (is_string($input)) {
            $questions = json_decode($input, true);
            if ($questions === null && json_last_error() !== JSON_ERROR_NONE) {
                return JsonResponse::error($response, 'VALIDATION_ERROR', 'Invalid JSON in questions_json', null, 400);
            }
        } elseif (is_array($input)) {
            $questions = $input;
        } elseif (is_object($input)) {
            $questions = json_decode(json_encode($input), true);
            if ($questions === null && json_last_error() !== JSON_ERROR_NONE) {
                return JsonResponse::error($response, 'VALIDATION_ERROR', 'Invalid object in questions_json', null, 400);
            }
        } else {
            return JsonResponse::error($response, 'VALIDATION_ERROR', 'questions_json must be a string, array, or object', null, 400);
        }

        if (!is_array($questions)) {
            return JsonResponse::error($response, 'VALIDATION_ERROR', 'Decoded questions_json must be an array', null, 400);
        }

        $data['questions_json'] = $questions;
        return null;
    }
}
