<?php

declare(strict_types=1);

namespace App\Presentation\Http\Respondent;

use App\Infrastructure\Database\RespondentRepository;
use App\Presentation\Http\JsonResponse;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

final class UpdateRespondentAction
{
    public function __construct(
        private RespondentRepository $respondentRepository
    ) {
    }

    public function __invoke(Request $request, Response $response): Response
    {
        /** @var array $respondent */
        $respondent = $request->getAttribute('respondent');
        $params = $request->getParsedBody();

        $name = trim($params['name'] ?? '');
        $email = trim($params['email'] ?? '');

        $errors = [];
        if (empty($name)) {
            $errors['name'] = 'お名前は必須です。';
        }
        if (empty($email)) {
            $errors['email'] = 'メールアドレスは必須です。';
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errors['email'] = '有効なメールアドレスを入力してください。';
        }

        if (!empty($errors)) {
            return JsonResponse::error($response, 'VALIDATION_ERROR', 'Validation failed', $errors, 400);
        }

        $this->respondentRepository->update((int)$respondent['id'], [
            'name' => $name,
            'email' => $email,
        ]);

        $updated = $this->respondentRepository->findById((int)$respondent['id']);

        return JsonResponse::success($response, [
            'id' => $updated['id'],
            'name' => $updated['name'],
            'email' => $updated['email'],
            'honorific' => $updated['honorific'],
            'line_display_name' => $updated['line_display_name'],
        ]);
    }
}
