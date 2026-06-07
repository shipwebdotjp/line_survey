<?php

declare(strict_types=1);

namespace Tests\Backend\Unit;

use App\Application\Survey\SaveResponseUseCase;
use App\Infrastructure\Database\RespondentRepository;
use App\Infrastructure\Database\ResponseRepository;
use App\Infrastructure\Database\SurveyRepository;
use App\Infrastructure\Line\IdTokenVerifier;
use App\Infrastructure\Mail\MailService;
use App\Infrastructure\Support\DateTimeHelper;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

final class SaveResponseUseCaseTest extends TestCase
{
    private MockObject $idTokenVerifier;
    private MockObject $respondentRepository;
    private MockObject $surveyRepository;
    private MockObject $responseRepository;
    private MockObject $mailService;
    private SaveResponseUseCase $useCase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->idTokenVerifier = $this->createMock(IdTokenVerifier::class);
        $this->respondentRepository = $this->createMock(RespondentRepository::class);
        $this->surveyRepository = $this->createMock(SurveyRepository::class);
        $this->responseRepository = $this->createMock(ResponseRepository::class);
        $this->mailService = $this->createMock(MailService::class);

        $this->useCase = new SaveResponseUseCase(
            $this->idTokenVerifier,
            $this->respondentRepository,
            $this->surveyRepository,
            $this->responseRepository,
            $this->mailService
        );
    }

    public function testExecuteLeavesEmailFieldsNullWhenSendingIsDisabled(): void
    {
        $this->configureCommonLookupMocks(false);

        $savedResponse = $this->createSavedResponse();

        $this->responseRepository
            ->expects($this->once())
            ->method('save')
            ->willReturn(123);

        $this->responseRepository
            ->expects($this->exactly(2))
            ->method('findById')
            ->with(123)
            ->willReturnOnConsecutiveCalls($savedResponse, $savedResponse);

        $this->responseRepository
            ->expects($this->never())
            ->method('update');

        $this->mailService
            ->expects($this->once())
            ->method('sendConfirmation')
            ->willReturn([
                'status' => 'skipped',
                'message' => 'Email sending is disabled for this survey.',
            ]);

        $result = $this->useCase->execute('survey-1', 'id-token', ['q1' => 'answer']);

        $this->assertNull($result['email_sent_at']);
        $this->assertNull($result['email_error']);
    }

    public function testExecuteMarksEmailAsSentAfterSuccessfulSend(): void
    {
        $this->configureCommonLookupMocks(true);

        $savedResponse = $this->createSavedResponse();
        $updatedResponse = $savedResponse;
        $updatedResponse['email_sent_at'] = '2026-06-07 12:00:00';
        $updatedResponse['email_error'] = null;

        $this->responseRepository
            ->expects($this->once())
            ->method('save')
            ->willReturn(123);

        $this->responseRepository
            ->expects($this->exactly(2))
            ->method('findById')
            ->with(123)
            ->willReturnOnConsecutiveCalls($savedResponse, $updatedResponse);

        $this->responseRepository
            ->expects($this->once())
            ->method('update')
            ->with(
                123,
                $this->callback(function (array $data): bool {
                    return isset($data['email_sent_at'])
                        && is_string($data['email_sent_at'])
                        && $data['email_sent_at'] !== ''
                        && array_key_exists('email_error', $data)
                        && $data['email_error'] === null;
                })
            );

        $this->mailService
            ->expects($this->once())
            ->method('sendConfirmation')
            ->willReturn([
                'status' => 'sent',
                'message' => 'Email sent successfully. ID: test-id',
            ]);

        $result = $this->useCase->execute('survey-1', 'id-token', ['q1' => 'answer']);

        $this->assertNotNull($result['email_sent_at']);
        $this->assertNull($result['email_error']);
    }

    public function testExecuteStoresEmailErrorWhenSendFails(): void
    {
        $this->configureCommonLookupMocks(true);

        $savedResponse = $this->createSavedResponse();
        $updatedResponse = $savedResponse;
        $updatedResponse['email_sent_at'] = null;
        $updatedResponse['email_error'] = 'Resend API key is not configured.';

        $this->responseRepository
            ->expects($this->once())
            ->method('save')
            ->willReturn(123);

        $this->responseRepository
            ->expects($this->exactly(2))
            ->method('findById')
            ->with(123)
            ->willReturnOnConsecutiveCalls($savedResponse, $updatedResponse);

        $this->responseRepository
            ->expects($this->once())
            ->method('update')
            ->with(
                123,
                $this->callback(function (array $data): bool {
                    return array_key_exists('email_sent_at', $data)
                        && $data['email_sent_at'] === null
                        && array_key_exists('email_error', $data)
                        && $data['email_error'] === 'Resend API key is not configured.';
                })
            );

        $this->mailService
            ->expects($this->once())
            ->method('sendConfirmation')
            ->willReturn([
                'status' => 'failed',
                'message' => 'Resend API key is not configured.',
            ]);

        $result = $this->useCase->execute('survey-1', 'id-token', ['q1' => 'answer']);

        $this->assertNull($result['email_sent_at']);
        $this->assertSame('Resend API key is not configured.', $result['email_error']);
    }

    public function testExecuteReturnsExistingResponseWithoutSendingMailWhenMultipleAnswersAreDisabled(): void
    {
        $this->idTokenVerifier
            ->expects($this->once())
            ->method('verify')
            ->with('id-token')
            ->willReturn([
                'sub' => 'line-user-1',
                'name' => 'Test User',
            ]);

        $this->respondentRepository
            ->expects($this->once())
            ->method('findBy')
            ->with(['line_user_id' => 'line-user-1'])
            ->willReturn([$this->createRespondent()]);

        $this->surveyRepository
            ->expects($this->once())
            ->method('findByPublicId')
            ->with('survey-1')
            ->willReturn($this->createSurvey([
                'allow_multiple' => false,
                'send_confirmation_email' => true,
            ]));

        $existingResponse = $this->createSavedResponse();

        $this->responseRepository
            ->expects($this->once())
            ->method('findBy')
            ->with([
                'survey_id' => 10,
                'respondent_id' => 20,
            ])
            ->willReturn([$existingResponse]);

        $this->responseRepository
            ->expects($this->never())
            ->method('save');

        $this->responseRepository
            ->expects($this->never())
            ->method('findById');

        $this->responseRepository
            ->expects($this->never())
            ->method('update');

        $this->mailService
            ->expects($this->never())
            ->method('sendConfirmation');

        $result = $this->useCase->execute('survey-1', 'id-token', ['q1' => 'answer']);

        $this->assertSame($existingResponse, $result);
    }

    private function configureCommonLookupMocks(bool $sendConfirmationEmail): void
    {
        $this->idTokenVerifier
            ->expects($this->once())
            ->method('verify')
            ->with('id-token')
            ->willReturn([
                'sub' => 'line-user-1',
                'name' => 'Test User',
            ]);

        $this->respondentRepository
            ->expects($this->once())
            ->method('findBy')
            ->with(['line_user_id' => 'line-user-1'])
            ->willReturn([$this->createRespondent()]);

        $this->surveyRepository
            ->expects($this->once())
            ->method('findByPublicId')
            ->with('survey-1')
            ->willReturn($this->createSurvey([
                'send_confirmation_email' => $sendConfirmationEmail,
                'allow_multiple' => false,
            ]));

        $this->responseRepository
            ->expects($this->once())
            ->method('findBy')
            ->with([
                'survey_id' => 10,
                'respondent_id' => 20,
            ])
            ->willReturn([]);
    }

    private function createRespondent(): array
    {
        return [
            'id' => 20,
            'line_user_id' => 'line-user-1',
            'name' => 'Test User',
            'email' => 'user@example.com',
            'honorific' => 'さん',
        ];
    }

    private function createSurvey(array $overrides = []): array
    {
        return array_merge([
            'id' => 10,
            'public_id' => 'survey-1',
            'title' => 'Test Survey',
            'description' => 'Desc',
            'questions_json' => ['elements' => []],
            'status' => 'published',
            'allow_multiple' => true,
            'allow_edit' => false,
            'send_confirmation_email' => true,
            'include_answers_in_email' => true,
            'starts_at' => null,
            'ends_at' => null,
        ], $overrides);
    }

    private function createSavedResponse(): array
    {
        return [
            'id' => 123,
            'survey_id' => 10,
            'respondent_id' => 20,
            'edit_token' => 'edit-token-1',
            'answer_json' => ['q1' => 'answer'],
            'survey_snapshot_json' => ['elements' => []],
            'submitted_at' => DateTimeHelper::formatTokyo(DateTimeHelper::nowTokyo()),
            'email_sent_at' => null,
            'email_error' => null,
        ];
    }
}
