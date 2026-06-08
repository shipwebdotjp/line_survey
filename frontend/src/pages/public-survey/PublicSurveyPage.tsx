import React, { useEffect, useState } from 'react';
import { useParams } from 'react-router-dom';
import { useLiffContext } from '../../features/liff/LiffContext';
import SurveyRenderer from '../../features/survey/SurveyRenderer';
import RespondentIdentification from '../../features/survey/RespondentIdentification';
import type { IdentifyStatus, Respondent, IdentifyResponse, SurveyResponse, SaveResponseResult } from '../../features/survey/types';
import type { Model } from 'survey-core';

interface SurveyData {
  can_answer: boolean;
  reason: 'not_published' | 'not_started' | 'closed' | null;
  survey: {
    title: string;
    description: string;
    questions_json: Record<string, any>;
    allow_multiple: boolean;
    allow_edit: boolean;
    starts_at: string | null;
    ends_at: string | null;
  } | null;
}

const PublicSurveyPage: React.FC = () => {
  const { public_id } = useParams<{ public_id: string }>();
  const { isLoggedIn, idToken } = useLiffContext();
  const [surveyData, setSurveyData] = useState<SurveyData | null>(null);
  const [identifyStatus, setIdentifyStatus] = useState<IdentifyStatus | null>(null);
  const [respondent, setRespondent] = useState<Respondent | null>(null);
  const [isLoading, setIsLoading] = useState(true);
  const [isIdentifying, setIsIdentifying] = useState(false);
  const [error, setError] = useState<string | null>(null);
  const [identifyError, setIdentifyError] = useState<string | null>(null);
  const [isSubmitting, setIsSubmitting] = useState(false);
  const [submitError, setSubmitError] = useState<string | null>(null);
  const [submittedResponse, setSubmittedResponse] = useState<SurveyResponse | null>(null);

  useEffect(() => {
    // LiffGate ensures we are initialized before this component renders,
    // but we still need isLoggedIn and idToken to fetch survey data.
    if (!isLoggedIn || !public_id || !idToken) return;

    const fetchData = async () => {
      try {
        setIsLoading(true);
        // 1. Fetch survey data
        const surveyResponse = await fetch(`/api/surveys/public/${public_id}`);
        const surveyResult = await surveyResponse.json();

        if (!surveyResponse.ok) {
          if (surveyResponse.status === 404) {
            setError('アンケートが見つかりませんでした。');
          } else {
            setError(surveyResult.error || '予期せぬエラーが発生しました。');
          }
          return;
        }

        setSurveyData(surveyResult.data);

        // 2. Identification
        const identifyResponse = await fetch('/api/liff/identify', {
          method: 'POST',
          headers: { 'Content-Type': 'application/json' },
          body: JSON.stringify({ id_token: idToken }),
        });
        const identifyResult: IdentifyResponse = await identifyResponse.json();

        if (!identifyResponse.ok) {
          setError(identifyResult.error || '本人確認に失敗しました。');
          return;
        }

        setIdentifyStatus(identifyResult.status);
        setRespondent(identifyResult.respondent);

      } catch (err) {
        setError('通信エラーが発生しました。');
      } finally {
        setIsLoading(false);
      }
    };

    fetchData();
  }, [isLoggedIn, public_id, idToken]);

  const handleManualSubmit = async (data: { name: string; email: string; honorific: string }) => {
    if (!idToken) return;
    try {
      setIsIdentifying(true);
      setIdentifyError(null);
      const response = await fetch('/api/liff/identify/manual', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({
          id_token: idToken,
          ...data,
        }),
      });
      const result: IdentifyResponse = await response.json();

      if (!response.ok) {
        setIdentifyError(result.error || '情報の保存に失敗しました。');
        return;
      }

      setIdentifyStatus(result.status);
      setRespondent(result.respondent);
    } catch (err) {
      setIdentifyError('通信エラーが発生しました。');
    } finally {
      setIsIdentifying(false);
    }
  };

  const handleSurveyComplete = async (sender: Model) => {
    if (!idToken || !public_id) return;
    try {
      setIsSubmitting(true);
      setSubmitError(null);
      const response = await fetch(`/api/surveys/public/${public_id}/responses`, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({
          id_token: idToken,
          answer_json: sender.data,
        }),
      });
      const result: SaveResponseResult = await response.json();

      if (!response.ok) {
        setSubmitError(result.error || 'アンケートの送信に失敗しました。');
        return;
      }

      if (result.data) {
        setSubmittedResponse(result.data);
      }
    } catch (err) {
      setSubmitError('通信エラーが発生しました。');
    } finally {
      setIsSubmitting(false);
    }
  };

  if (isLoading && !error) {
    return (
      <div style={{ padding: '2rem', textAlign: 'center' }}>
        <p>読み込み中...</p>
      </div>
    );
  }

  if (error) {
    return (
      <div style={{ padding: '2rem', textAlign: 'center' }}>
        <h1 style={{ fontSize: '1.5rem', marginBottom: '1rem' }}>エラー</h1>
        <p>{error}</p>
      </div>
    );
  }

  if (!surveyData || !surveyData.survey || !surveyData.survey.questions_json) {
    return (
      <div style={{ padding: '2rem', textAlign: 'center' }}>
        <h1 style={{ fontSize: '1.5rem', marginBottom: '1rem' }}>エラー</h1>
        <p>アンケートデータの取得に失敗しました。</p>
      </div>
    );
  }

  if (!surveyData.can_answer) {
    let title = '回答不可';
    let message = '現在、このアンケートには回答できません。';

    if (surveyData.reason === 'not_published') {
      message = 'このアンケートは現在公開されていません。';
    } else if (surveyData.reason === 'not_started') {
      title = '開始前';
      message = 'このアンケートはまだ開始されていません。';
      if (surveyData.survey.starts_at) {
        message += `\n開始予定: ${surveyData.survey.starts_at}`;
      }
    } else if (surveyData.reason === 'closed') {
      title = '終了';
      message = 'このアンケートは終了しました。';
    }

    return (
      <div style={{ padding: '2rem', textAlign: 'center' }}>
        <h1 style={{ fontSize: '1.5rem', marginBottom: '1rem' }}>{title}</h1>
        <p style={{ whiteSpace: 'pre-wrap' }}>{message}</p>
      </div>
    );
  }

  const showSurvey = identifyStatus === 'existing' || identifyStatus === 'matched' || identifyStatus === 'manual_saved';

  if (submittedResponse) {
    const editUrl = `${window.location.origin}/s/${public_id}/r/${submittedResponse.edit_token}/edit`;

    return (
      <div style={{ padding: '2rem', textAlign: 'center', maxWidth: '600px', margin: '0 auto' }}>
        <h1 style={{ fontSize: '1.5rem', marginBottom: '1rem' }}>回答が完了しました</h1>
        <p style={{ marginBottom: '1rem' }}>ご協力ありがとうございました。</p>

        {submittedResponse.email_error ? (
          <p style={{ marginBottom: '1.5rem', color: '#dc3545' }}>
            ※回答控えメールの送信に失敗しました。回答自体は保存されています。
          </p>
        ) : submittedResponse.email_sent_at ? (
          <p style={{ marginBottom: '1.5rem', color: '#28a745' }}>
            回答の控えをメールでお送りしました。ご確認ください。
          </p>
        ) : null}

        {surveyData.survey?.allow_edit && (
          <div style={{ marginTop: '2rem', padding: '1rem', background: '#f8f9fa', borderRadius: '8px' }}>
            <p style={{ fontSize: '0.9rem', marginBottom: '0.5rem', fontWeight: 'bold' }}>回答の修正用URL:</p>
            <p style={{ fontSize: '0.8rem', wordBreak: 'break-all', color: '#007bff' }}>
              <a href={editUrl}>{editUrl}</a>
            </p>
            <p style={{ fontSize: '0.8rem', marginTop: '0.5rem', color: '#666' }}>
              ※このURLを保存しておくと、後から回答を修正できます。
            </p>
          </div>
        )}
      </div>
    );
  }

  return (
    <div style={{ padding: '1rem', maxWidth: '600px', margin: '0 auto' }}>
      <h1 style={{ fontSize: '1.5rem', marginBottom: '0.5rem' }}>{surveyData.survey?.title}</h1>
      {surveyData.survey?.description && (
        <p style={{ marginBottom: '1.5rem', color: '#666' }}>{surveyData.survey.description}</p>
      )}

      {identifyStatus && (
        <RespondentIdentification
          status={identifyStatus}
          respondent={respondent}
          onManualSubmit={handleManualSubmit}
          isSubmitting={isIdentifying}
          submitError={identifyError}
        />
      )}

      {showSurvey && (
        <div style={{ marginTop: '1rem' }}>
          {submitError && (
            <div style={{
              padding: '1rem',
              marginBottom: '1rem',
              backgroundColor: '#fff5f5',
              color: '#c53030',
              borderRadius: '4px',
              border: '1px solid #feb2b2'
            }}>
              {submitError}
            </div>
          )}
          <SurveyRenderer
            questions={surveyData.survey?.questions_json}
            onComplete={handleSurveyComplete}
            isSubmitting={isSubmitting}
          />
        </div>
      )}
    </div>
  );
};

export default PublicSurveyPage;
