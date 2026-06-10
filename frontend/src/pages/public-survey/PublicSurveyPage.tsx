import React, { useEffect, useState } from 'react';
import { useParams, useNavigate } from 'react-router-dom';
import { useLiffContext } from '../../features/liff/LiffContext';
import { fetchWithSession } from '../../lib/publicApi';
import SurveyRenderer from '../../features/survey/SurveyRenderer';
import RespondentIdentification from '../../features/survey/RespondentIdentification';
import type { IdentifyStatus, Respondent, IdentifyResponse, SurveyResponse, SaveResponseResult, SurveyData } from '../../features/survey/types';
import type { Model } from 'survey-core';
import { createLiffUrl } from '../../lib/liffUrl';

const PublicSurveyPage: React.FC = () => {
  const { public_id } = useParams<{ public_id: string }>();
  const { isLoggedIn, idToken, identify } = useLiffContext();
  const navigate = useNavigate();
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
  const [existingResponse, setExistingResponse] = useState<SurveyResponse | null>(null);
  const isDebugMode = import.meta.env.DEV || (typeof window !== 'undefined' && new URLSearchParams(window.location.search).has('debug'));

  useEffect(() => {
    if (isLoading && !error) {
      document.title = '読み込み中...';
    } else if (error) {
      document.title = 'エラー';
    } else if (submittedResponse) {
      document.title = '回答完了';
    } else if (surveyData) {
      if (!surveyData.can_answer) {
        if (surveyData.reason === 'not_started') {
          document.title = '開始前';
        } else if (surveyData.reason === 'closed') {
          document.title = '終了';
        } else {
          document.title = '回答不可';
        }
      } else {
        document.title = surveyData.survey?.title || 'アンケート回答';
      }
    }
  }, [isLoading, error, surveyData, submittedResponse]);

  useEffect(() => {
    // LiffGate ensures we are initialized before this component renders.

    if (!public_id) {
      setError('アンケートIDが指定されていません。');
      setIsLoading(false);
      return;
    }

    if (!isLoggedIn || !idToken) {
      // If we are not logged in here, it means liff.init with withLoginOnExternalBrowser: true
      // did not result in a login (or it's still in progress, but LiffGate should have waited for initialization).
      // We explicitly set error to avoid infinite loading.
      setError('LINEログインが必要です。');
      setIsLoading(false);
      return;
    }

    const fetchData = async () => {
      try {
        setIsLoading(true);

        const fetchOptions = {
          onSessionRequired: identify,
        };

        // 1. Fetch survey data
        const surveyResponse = await fetchWithSession(`/api/surveys/public/${public_id}`, {}, fetchOptions);
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
        const identifyResponse = await fetchWithSession('/api/liff/identify', {
          method: 'POST',
          body: JSON.stringify({ id_token: idToken }),
        }, fetchOptions);
        const identifyResult: IdentifyResponse = await identifyResponse.json();

        if (!identifyResponse.ok) {
          setError(identifyResult.error || '本人確認に失敗しました。');
          return;
        }

        setIdentifyStatus(identifyResult.status);
        setRespondent(identifyResult.respondent);

        // 3. Check for existing response
        const responseRes = await fetchWithSession(`/api/surveys/public/${public_id}/responses/current`, {}, fetchOptions);
        if (responseRes.ok) {
          const responseResult = await responseRes.json();
          setExistingResponse(responseResult.data);
        }

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
      const response = await fetchWithSession('/api/liff/identify/manual', {
        method: 'POST',
        body: JSON.stringify({
          id_token: idToken,
          ...data,
        }),
      }, { onSessionRequired: identify });
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
      const response = await fetchWithSession(`/api/surveys/public/${public_id}/responses`, {
        method: 'POST',
        body: JSON.stringify({
          answer_json: sender.data,
        }),
      }, { onSessionRequired: identify });
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
      <div className="public-container">
        <div style={{ textAlign: 'center', padding: '2rem' }}>
          <p>読み込み中...</p>
        </div>
      </div>
    );
  }

  if (error) {
    return (
      <div className="public-container">
        <div className="public-card" style={{ textAlign: 'center' }}>
          <h1 style={{ fontSize: '1.5rem', marginBottom: '1rem' }}>エラー</h1>
          <p>{error}</p>
        </div>
      </div>
    );
  }

  if (!surveyData || !surveyData.survey || !surveyData.survey.questions_json) {
    return (
      <div className="public-container">
        <div className="public-card" style={{ textAlign: 'center' }}>
          <h1 style={{ fontSize: '1.5rem', marginBottom: '1rem' }}>エラー</h1>
          <p>アンケートデータの取得に失敗しました。</p>
        </div>
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
      <div className="public-container">
        <div className="public-card" style={{ textAlign: 'center' }}>
          <h1 style={{ fontSize: '1.5rem', marginBottom: '1rem' }}>{title}</h1>
          <p style={{ whiteSpace: 'pre-wrap' }}>{message}</p>
        </div>
      </div>
    );
  }

  const showSurvey = identifyStatus === 'existing' || identifyStatus === 'matched' || identifyStatus === 'manual_saved';

  if (submittedResponse) {
    const editUrl = createLiffUrl(`/s/${public_id}/r/${submittedResponse.edit_token}/edit`);

    return (
      <div className="public-container">
        <div className="public-card" style={{ textAlign: 'center' }}>
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
              <p style={{ fontSize: '0.8rem', wordBreak: 'break-all', color: '#4f46e5' }}>
                <a href={editUrl}>{editUrl}</a>
              </p>
              <p style={{ fontSize: '0.8rem', marginTop: '0.5rem', color: '#666' }}>
                ※後から回答を修正するためのURLはメールでもお送りしています。
              </p>
            </div>
          )}
        </div>
      </div>
    );
  }

  if (existingResponse && !surveyData.survey?.allow_multiple) {
    return (
      <div className="public-container">
        <div className="public-card" style={{ textAlign: 'center' }}>
          <h1 style={{ fontSize: '1.5rem', marginBottom: '1rem' }}>{surveyData.survey?.title}</h1>
          <p style={{ marginBottom: '2rem', color: '#6b7280' }}>既にご回答いただいています。</p>

          <div style={{ display: 'flex', flexDirection: 'column', gap: '1rem', alignItems: 'center' }}>
            <button
              onClick={() => navigate(`/s/${public_id}/r/${existingResponse.edit_token}`)}
              className="public-btn public-btn-primary public-btn-full"
            >
              回答内容を確認する
            </button>

            {surveyData.survey?.allow_edit && (
              <button
                onClick={() => navigate(`/s/${public_id}/r/${existingResponse.edit_token}/edit`)}
                className="public-btn public-btn-secondary public-btn-full"
              >
                回答を修正する
              </button>
            )}

            <button
              onClick={() => navigate('/s')}
              className="public-btn public-btn-secondary public-btn-full"
              style={{ marginTop: '1rem' }}
            >
              回答履歴へ
            </button>
          </div>
        </div>
      </div>
    );
  }

  return (
    <div className="public-container">
      <div className="public-card">
        <h1 style={{ fontSize: '1.5rem', marginBottom: '0.5rem' }}>{surveyData.survey?.title}</h1>
        {surveyData.survey?.description && (
          <p style={{ marginBottom: '1.5rem', color: '#6b7280' }}>{surveyData.survey.description}</p>
        )}

        {isDebugMode && (
          <details style={{
            marginBottom: '1rem',
            padding: '0.75rem 1rem',
            border: '1px solid #d1d5db',
            borderRadius: '8px',
            background: '#f9fafb'
          }}>
            <summary style={{ cursor: 'pointer', fontWeight: 'bold' }}>Debug info</summary>
            <pre style={{
              margin: '0.75rem 0 0',
              whiteSpace: 'pre-wrap',
              wordBreak: 'break-word',
              fontSize: '0.8rem'
            }}>
              {JSON.stringify({
                public_id,
                isLoggedIn,
                hasIdToken: !!idToken,
                isLoading,
                error,
                identifyStatus,
                respondent,
                surveyLoaded: !!surveyData,
                canAnswer: surveyData?.can_answer ?? null,
                existingResponseLoaded: !!existingResponse,
                showSurvey,
              }, null, 2)}
            </pre>
          </details>
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
                backgroundColor: '#fef2f2',
                color: '#991b1b',
                borderRadius: '4px',
                border: '1px solid #fecaca'
              }}>
                {submitError}
              </div>
            )}
            <SurveyRenderer
              questions={surveyData.survey?.questions_json}
              onComplete={handleSurveyComplete}
              isSubmitting={isSubmitting}
              isPublic={true}
            />
          </div>
        )}
      </div>
    </div>
  );
};

export default PublicSurveyPage;
