<?php

declare(strict_types=1);

namespace App\Application\Survey;

use App\Infrastructure\Database\RespondentRepository;
use App\Infrastructure\Database\ResponseDraftRepository;
use App\Infrastructure\Database\ResponseRepository;
use App\Infrastructure\Database\SurveyRepository;
use App\Infrastructure\Mail\MailService;
use App\Infrastructure\Support\DateTimeHelper;
use App\Infrastructure\Support\IdGenerator;

final class SaveResponseUseCase
{
    use SurveyResolutionTrait;

    public function __construct(
        private RespondentRepository $respondentRepository,
        private SurveyRepository $surveyRepository,
        private ResponseRepository $responseRepository,
        private ResponseDraftRepository $responseDraftRepository,
        private MailService $mailService,
        private SurveyAvailabilityValidator $surveyAvailabilityValidator
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

        $this->surveyAvailabilityValidator->assertCanRespond($survey);

        if (!($survey['allow_multiple'] ?? false)) {
            $existingResponses = $this->responseRepository->findBy([
                'survey_id' => $survey['id'],
                'respondent_id' => $respondent['id']
            ]);
            if (!empty($existingResponses)) {
                $this->responseDraftRepository->deleteBySurveyAndRespondent($survey['id'], $respondent['id']);
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

        $this->responseDraftRepository->deleteBySurveyAndRespondent($survey['id'], $respondent['id']);

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
}
