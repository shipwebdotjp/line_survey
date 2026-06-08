<?php

declare(strict_types=1);

namespace App\Application\Survey;

use App\Infrastructure\Database\RespondentRepository;
use App\Infrastructure\Database\ResponseRepository;
use App\Infrastructure\Database\SurveyRepository;
use App\Infrastructure\Line\IdTokenVerifier;
use App\Infrastructure\Mail\MailService;
use App\Infrastructure\Support\DateTimeHelper;
use RuntimeException;

final class UpdateResponseUseCase
{
    public function __construct(
        private IdTokenVerifier $idTokenVerifier,
        private RespondentRepository $respondentRepository,
        private SurveyRepository $surveyRepository,
        private ResponseRepository $responseRepository,
        private MailService $mailService
    ) {
    }

    /**
     * @param string $publicId
     * @param string $editToken
     * @param string $idToken
     * @param array $answerJson
     * @return array
     * @throws RuntimeException
     */
    public function execute(string $publicId, string $editToken, string $idToken, array $answerJson): array
    {
        // 1. Verify ID Token
        $claims = $this->idTokenVerifier->verify($idToken);
        $lineUserId = $claims['sub'];

        // 2. Resolve respondent
        $respondents = $this->respondentRepository->findBy(['line_user_id' => $lineUserId]);
        if (empty($respondents)) {
            throw new RuntimeException('Respondent not found', 404);
        }
        $respondent = $respondents[0];

        // 3. Get survey and validate availability
        $survey = $this->surveyRepository->findByPublicId($publicId);
        if (!$survey) {
            throw new RuntimeException('Survey not found', 404);
        }

        if (!($survey['allow_edit'] ?? false)) {
            throw new RuntimeException('Editing is not allowed for this survey', 403);
        }

        $this->validateSurveyAvailability($survey);

        // 4. Find and validate response
        $responses = $this->responseRepository->findBy(['edit_token' => $editToken]);
        if (empty($responses)) {
            throw new RuntimeException('Response not found', 404);
        }
        $response = $responses[0];

        if ((int)$response['respondent_id'] !== (int)$respondent['id']) {
            throw new RuntimeException('Unauthorized to edit this response', 403);
        }

        if ((int)$response['survey_id'] !== (int)$survey['id']) {
            throw new RuntimeException('Response does not belong to this survey', 400);
        }

        // 5. Update response
        $updateData = [
            'answer_json' => $answerJson,
            'survey_snapshot_json' => $survey['questions_json'],
        ];

        $this->responseRepository->update((int)$response['id'], $updateData);
        $updatedResponse = $this->responseRepository->findById((int)$response['id']);

        // 6. Send confirmation email
        $mailResult = $this->mailService->sendConfirmation($respondent, $survey, $updatedResponse, true);

        // 7. Record email status
        if (($mailResult['status'] ?? null) === 'sent') {
            $this->responseRepository->update((int)$response['id'], [
                'email_sent_at' => DateTimeHelper::formatTokyo(DateTimeHelper::nowTokyo()),
                'email_error' => null,
            ]);
        } elseif (($mailResult['status'] ?? null) === 'failed') {
            $this->responseRepository->update((int)$response['id'], [
                'email_sent_at' => null,
                'email_error' => $mailResult['message'],
            ]);
        }

        return $this->responseRepository->findById((int)$response['id']);
    }

    private function validateSurveyAvailability(array $survey): void
    {
        // Status check
        if ($survey['status'] !== 'published') {
            throw new RuntimeException('Survey is not published', 403);
        }

        // Date check
        $now = DateTimeHelper::nowTokyo();
        $endsAt = $survey['ends_at'] ? DateTimeHelper::parseTokyo($survey['ends_at']) : null;

        if ($endsAt && $now > $endsAt) {
            throw new RuntimeException('Survey has already ended', 403);
        }
    }
}
