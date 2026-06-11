<?php

declare(strict_types=1);

namespace App\Presentation\Http\Admin\RespondentMaster;

use App\Application\Admin\RespondentMaster\CreateRespondentMasterUseCase;
use App\Application\Admin\RespondentMaster\ValidationException;
use App\Presentation\Http\JsonResponse;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

class CreateRespondentMasterAction
{
    public function __construct(
        private CreateRespondentMasterUseCase $useCase
    ) {
    }

    public function __invoke(Request $request, Response $response): Response
    {
        $data = $request->getParsedBody();

        if (!is_array($data)) {
            return JsonResponse::error($response, 'VALIDATION_ERROR', 'Invalid request body', null, 400);
        }

        $errors = $this->validate($data);
        if (!empty($errors)) {
            return JsonResponse::error($response, 'VALIDATION_ERROR', 'Validation Error', $errors, 400);
        }

        try {
            $id = $this->useCase->execute($data);
            return JsonResponse::success($response, ['id' => $id], 201);
        } catch (ValidationException $e) {
            return JsonResponse::error($response, 'VALIDATION_ERROR', $e->getMessage(), $e->getDetails(), 400);
        }
    }

    private function validate(array $data): array
    {
        $errors = [];
        if (empty($data['master_code'])) {
            $errors['master_code'] = 'マスターコードは必須です。';
        }
        if (empty($data['line_display_name'])) {
            $errors['line_display_name'] = 'LINE表示名は必須です。';
        }
        if (empty($data['name'])) {
            $errors['name'] = '氏名は必須です。';
        }
        if (empty($data['email'])) {
            $errors['email'] = 'メールアドレスは必須です。';
        } elseif (!filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
            $errors['email'] = '有効なメールアドレスを入力してください。';
        }
        return $errors;
    }
}
