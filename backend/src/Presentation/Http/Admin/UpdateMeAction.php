<?php

declare(strict_types=1);

namespace App\Presentation\Http\Admin;

use App\Infrastructure\Database\UserRepository;
use App\Presentation\Http\JsonResponse;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Illuminate\Database\ConnectionInterface;

final class UpdateMeAction
{
    public function __construct(
        private UserRepository $userRepository,
        private ConnectionInterface $db
    ) {
    }

    public function __invoke(Request $request, Response $response): Response
    {
        $user = $request->getAttribute('owner_user');
        $data = $request->getParsedBody() ?? [];

        $email = isset($data['email']) ? trim((string)$data['email']) : '';

        if ($email === '') {
            return JsonResponse::error($response, 'VALIDATION_ERROR', 'メールアドレスは必須です。', null, 422);
        }

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return JsonResponse::error($response, 'VALIDATION_ERROR', '正当なメールアドレス形式で入力してください。', null, 422);
        }

        // Check uniqueness
        $sql = 'SELECT id FROM users WHERE email = ? AND id <> ? LIMIT 1';
        $existingUser = $this->db->selectOne($sql, [$email, $user['id']]);

        if ($existingUser) {
            return JsonResponse::error($response, 'VALIDATION_ERROR', 'このメールアドレスは既に使用されています。', null, 422);
        }

        try {
            $this->userRepository->update((int)$user['id'], ['email' => $email]);
            $updatedUser = $this->userRepository->findById((int)$user['id']);

            return JsonResponse::success($response, [
                'user' => [
                    'id' => $updatedUser['id'],
                    'line_user_id' => $updatedUser['line_user_id'],
                    'line_display_name' => $updatedUser['line_display_name'],
                    'line_picture_url' => $updatedUser['line_picture_url'],
                    'email' => $updatedUser['email'],
                    'role' => $updatedUser['role'],
                    'created_at' => $updatedUser['created_at'],
                    'updated_at' => $updatedUser['updated_at'],
                ]
            ]);
        } catch (\Throwable $e) {
            error_log((string)$e);
            return JsonResponse::error($response, 'INTERNAL_ERROR', 'An internal error occurred', null, 500);
        }
    }
}
