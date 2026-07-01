import React, { useEffect, useState, useRef } from 'react';
import { useParams, useNavigate } from 'react-router-dom';
import { useLiffContext } from '../../features/liff/LiffContext';
import { fetchWithSession, getResponseDraft, saveResponseDraft, deleteResponseDraft, getResponseHistory } from '../../lib/publicApi';
import SurveyRenderer from '../../features/survey/SurveyRenderer';
import RespondentIdentification from '../../features/survey/RespondentIdentification';
import type { IdentifyStatus, Respondent, IdentifyResponse, SurveyResponse, SaveResponseResult, SurveyData, ResponseDraft, ResponseHistoryItem } from '../../features/survey/types';
import ResponseHistoryList from '../../features/survey/ResponseHistoryList';
import { getInitialAnswerJson } from '../../features/survey/surveyPrefill';
import { Model } from 'survey-core';
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
  const [history, setHistory] = useState<ResponseHistoryItem[] | null>(null);
  const [draft, setDraft] = useState<ResponseDraft | null>(null);
  const [autoSaveError, setAutoSaveError] = useState<string | null>(null);
  const isDebugMode = import.meta.env.DEV || (typeof window !== 'undefined' && new URLSearchParams(window.location.search).has('debug'));
  const canEditResponse = !!surveyData?.survey?.allow_edit && !!surveyData?.can_answer;

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
          onSessionRequired: () => identify(public_id),
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
        const identifyResponse = await fetch('/api/liff/identify', {
          method: 'POST',
          headers: { 'Content-Type': 'application/json' },
          body: JSON.stringify({
            id_token: idToken,
            public_id: public_id,
          }),
        });
        const identifyResult: IdentifyResponse = await identifyResponse.json();

        if (!identifyResponse.ok) {
          setError(identifyResult.error || '本人確認に失敗しました。');
          return;
        }

        setIdentifyStatus(identifyResult.status);
        setRespondent(identifyResult.respondent);

        if (!surveyResult.data.can_answer) {
          // If cannot answer, fetch history for this survey if identified
          try {
            const historyData = await getResponseHistory(public_id, () => identify(public_id));
            setHistory(historyData);
          } catch (err) {
            console.error('Failed to fetch response history locally:', err);
            setHistory(null);
          }
          return;
        }

        // 3. Check for existing response
        const responseRes = await fetchWithSession(`/api/surveys/public/${public_id}/responses/current`, {}, fetchOptions);
        let hasExistingResponse = false;
        if (responseRes.ok) {
          const responseResult = await responseRes.json();
          setExistingResponse(responseResult.data);
          if (responseResult.data && !surveyResult.data?.survey?.allow_multiple) {
            hasExistingResponse = true;
          }
        }

        // 4. Fetch draft if no existing response (that blocks new answers)
        if (!hasExistingResponse) {
          const draftResult = await getResponseDraft(public_id, () => identify(public_id));
          setDraft(draftResult.draft);
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
          public_id: public_id,
          ...data,
        }),
      }, {
        onSessionRequired: () => identify(public_id!)
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

    // Stop auto-save during submission
    if (autoSaveTimerRef.current) {
      clearTimeout(autoSaveTimerRef.current);
    }
    isAutoSaveDisabledRef.current = true;

    try {
      setIsSubmitting(true);
      setSubmitError(null);
      const response = await fetchWithSession(`/api/surveys/public/${public_id}/responses`, {
        method: 'POST',
        body: JSON.stringify({
          answer_json: sender.data,
        }),
      }, {
        onSessionRequired: () => identify(public_id)
      });
      const result: SaveResponseResult = await response.json();

      if (!response.ok) {
        setSubmitError(result.error || 'アンケートの送信に失敗しました。');
        isAutoSaveDisabledRef.current = false;
        return;
      }

      if (result.data) {
        setSubmittedResponse(result.data);
        // Delete draft after successful submission (already handled by backend but good to sync)
        try {
          await deleteResponseDraft(public_id, () => identify(public_id));
        } catch (e) {
          // Ignore draft deletion error on frontend as it's not critical
          console.error('Failed to delete draft on frontend', e);
        }
      }
    } catch (err) {
      setSubmitError('通信エラーが発生しました。');
      isAutoSaveDisabledRef.current = false;
    } finally {
      setIsSubmitting(false);
    }
  };

  const autoSaveTimerRef = useRef<ReturnType<typeof setTimeout> | null>(null);
  const isAutoSaveDisabledRef = useRef(false);
  const handleValueChanged = (sender: Model) => {
    if (!public_id || isAutoSaveDisabledRef.current) return;

    if (autoSaveTimerRef.current) {
      clearTimeout(autoSaveTimerRef.current);
    }

    autoSaveTimerRef.current = setTimeout(async () => {
      if (isAutoSaveDisabledRef.current) return;
      try {
        setAutoSaveError(null);
        await saveResponseDraft(public_id, sender.data, () => identify(public_id));
      } catch (err) {
        setAutoSaveError('一時保存に失敗しました。入力は続けられます。');
      }
    }, 5000);
  };

  useEffect(() => {
    return () => {
      if (autoSaveTimerRef.current) {
        clearTimeout(autoSaveTimerRef.current);
      }
    };
  }, []);

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
      title = '終了';
      message = 'このアンケートは終了しました。';
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
          <p style={{ whiteSpace: 'pre-wrap', marginBottom: history && history.length > 0 ? '2rem' : '0' }}>{message}</p>

          {history && history.length > 0 && (
            <div style={{ textAlign: 'left', marginTop: '2rem' }}>
              <h2 style={{ fontSize: '1.1rem', marginBottom: '1rem', fontWeight: 'bold', borderBottom: '1px solid #e5e7eb', paddingBottom: '0.5rem' }}>
                回答履歴
              </h2>
              <ResponseHistoryList history={history} surveyPublicId={public_id} />
            </div>
          )}
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

          {canEditResponse && (
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

            {canEditResponse && (
              <button
                onClick={() => navigate(`/s/${public_id}/r/${existingResponse.edit_token}/edit`)}
                className="public-btn public-btn-secondary public-btn-full"
              >
                回答を修正する
              </button>
            )}

            <button
              onClick={() => navigate(`/s?public_id=${public_id}`)}
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
            {autoSaveError && (
              <div style={{
                padding: '0.5rem 1rem',
                marginBottom: '1rem',
                backgroundColor: '#fffbeb',
                color: '#92400e',
                borderRadius: '4px',
                border: '1px solid #fef3c7',
                fontSize: '0.875rem'
              }}>
                {autoSaveError}
              </div>
            )}
            <SurveyRenderer
              questions={surveyData.survey?.questions_json}
              initialValues={getInitialAnswerJson(surveyData.survey?.questions_json, respondent, draft)}
              onComplete={handleSurveyComplete}
              onValueChanged={handleValueChanged}
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
