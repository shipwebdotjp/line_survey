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
        if (!is_array($respondent)) {
            throw new \RuntimeException('Respondent attribute must be an array. Ensure AuthSessionMiddleware is active.');
        }

        $params = $request->getParsedBody();
        if (!is_array($params)) {
            $params = [];
        }

        $name = trim((string)($params['name'] ?? ''));
        $email = trim((string)($params['email'] ?? ''));

        $errors = [];
        if (empty($name)) {
            $errors['name'] = 'お名前は必須です。';
        } elseif (mb_strlen($name) > 255) {
            $errors['name'] = 'お名前は255文字以内で入力してください。';
        }

        if (empty($email)) {
            $errors['email'] = 'メールアドレスは必須です。';
        } elseif (mb_strlen($email) > 320) {
            $errors['email'] = 'メールアドレスは320文字以内で入力してください。';
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

        if (!$updated) {
            return JsonResponse::error($response, 'NOT_FOUND', 'Respondent not found', null, 404);
        }

        return JsonResponse::success($response, [
            'id' => $updated['id'],
            'name' => $updated['name'],
            'email' => $updated['email'],
            'line_display_name' => $updated['line_display_name'],
        ]);
    }
}
