<?php

declare(strict_types=1);

namespace App\Presentation\Http\Admin\Respondent;

use App\Application\Admin\Respondent\UpdateRespondentUseCase;
use App\Presentation\Http\JsonResponse;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

class UpdateRespondentAction
{
    public function __construct(
        private UpdateRespondentUseCase $useCase
    ) {
    }

    public function __invoke(Request $request, Response $response, array $args): Response
    {
        $id = (int)$args['id'];
        $data = $request->getParsedBody();

        if (!is_array($data)) {
            return JsonResponse::error($response, 'VALIDATION_ERROR', 'Invalid request body', null, 400);
        }

        $errors = [];
        if (empty($data['name'])) {
            $errors['name'] = '氏名は必須です。';
        }
        if (empty($data['email'])) {
            $errors['email'] = 'メールアドレスは必須です。';
        } elseif (!filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
            $errors['email'] = '有効なメールアドレスを入力してください。';
        }

        if (!empty($errors)) {
            return JsonResponse::error($response, 'VALIDATION_ERROR', 'Validation Error', $errors, 400);
        }

        $success = $this->useCase->execute($id, $data);

        if (!$success) {
            return JsonResponse::error($response, 'NOT_FOUND', 'Respondent not found', null, 404);
        }

        return JsonResponse::success($response, ['success' => true]);
    }
}
