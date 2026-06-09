<?php

declare(strict_types=1);

namespace App\Application\Survey;

use App\Infrastructure\Database\RespondentRepository;
use App\Infrastructure\Database\ResponseRepository;
use App\Infrastructure\Database\SurveyRepository;
use App\Infrastructure\Line\IdTokenVerifier;
use App\Infrastructure\Mail\MailService;
use App\Infrastructure\Support\DateTimeHelper;
use App\Infrastructure\Support\IdGenerator;
use RuntimeException;

final class SaveResponseUseCase
{
    use SurveyResolutionTrait;

    public function __construct(
        private RespondentRepository $respondentRepository,
        private SurveyRepository $surveyRepository,
        private ResponseRepository $responseRepository,
        private MailService $mailService
    ) {
    }

    /**
     * @param string $publicId
     * @param array $respondent
     * @param array $answerJson
     * @return array
     * @throws RuntimeException
     */
    public function execute(string $publicId, array $respondent, array $answerJson): array
    {
        $respondent = $this->resolveRespondent($respondent);
        $survey = $this->resolveSurveyByPublicId($publicId);

        $this->validateSurveyAvailability($survey);

        if (!($survey['allow_multiple'] ?? false)) {
            $existingResponses = $this->responseRepository->findBy([
                'survey_id' => $survey['id'],
                'respondent_id' => $respondent['id']
            ]);
            if (!empty($existingResponses)) {
                return $existingResponses[0];
            }
        }

        $now = DateTimeHelper::nowTokyo();
        $submittedAt = DateTimeHelper::formatTokyo($now);
        $editToken = IdGenerator::generateEditToken();

        $responseData = [
            'survey_id' => $survey['id'],
            'respondent_id' => $respondent['id'],
            'edit_token' => $editToken,
            'answer_json' => $answerJson,
            'survey_snapshot_json' => $survey['questions_json'],
            'submitted_at' => $submittedAt,
        ];

        $responseId = $this->responseRepository->save($responseData);
        $savedResponse = $this->responseRepository->findById($responseId);

        $mailResult = $this->mailService->sendConfirmation($respondent, $survey, $savedResponse);

        if (($mailResult['status'] ?? null) === 'sent') {
            $this->responseRepository->update($responseId, [
                'email_sent_at' => DateTimeHelper::formatTokyo(DateTimeHelper::nowTokyo()),
                'email_error' => null,
            ]);
        } elseif (($mailResult['status'] ?? null) === 'failed') {
            $this->responseRepository->update($responseId, [
                'email_sent_at' => null,
                'email_error' => $mailResult['message'],
            ]);
        }

        return $this->responseRepository->findById($responseId);
    }

    private function validateSurveyAvailability(array $survey): void
    {
        if ($survey['status'] !== 'published') {
            throw new RuntimeException('Survey is not published', 403);
        }

        $now = DateTimeHelper::nowTokyo();
        $startsAt = $survey['starts_at'] ? DateTimeHelper::parseTokyo($survey['starts_at']) : null;
        $endsAt = $survey['ends_at'] ? DateTimeHelper::parseTokyo($survey['ends_at']) : null;

        if ($startsAt && $now < $startsAt) {
            throw new RuntimeException('Survey has not started yet', 403);
        }

        if ($endsAt && $now > $endsAt) {
            throw new RuntimeException('Survey has already ended', 403);
        }
    }
}
