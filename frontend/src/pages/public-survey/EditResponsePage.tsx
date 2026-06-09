import React, { useEffect, useState } from 'react';
import { useParams, useNavigate } from 'react-router-dom';
import { useLiffContext } from '../../features/liff/LiffContext';
import { fetchWithSession } from '../../lib/publicApi';
import SurveyRenderer from '../../features/survey/SurveyRenderer';
import type { SurveyResponse, SaveResponseResult, SurveyData } from '../../features/survey/types';
import type { Model } from 'survey-core';

const EditResponsePage: React.FC = () => {
  const { public_id, edit_token } = useParams<{ public_id: string, edit_token: string }>();
  const { isLoggedIn, idToken, identify } = useLiffContext();
  const navigate = useNavigate();

  const [surveyData, setSurveyData] = useState<SurveyData | null>(null);
  const [existingResponse, setExistingResponse] = useState<SurveyResponse | null>(null);
  const [isLoading, setIsLoading] = useState(true);
  const [error, setError] = useState<string | null>(null);
  const [isSubmitting, setIsSubmitting] = useState(false);
  const [submitError, setSubmitError] = useState<string | null>(null);
  const [isSuccess, setIsSuccess] = useState(false);
  const [emailStatus, setEmailStatus] = useState<{ sent: boolean; error: string | null } | null>(null);

  useEffect(() => {
    if (!public_id || !edit_token) {
      setError('不正なURLです。');
      setIsLoading(false);
      return;
    }

    if (!isLoggedIn || !idToken) {
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
        const surveyRes = await fetchWithSession(`/api/surveys/public/${public_id}`, {}, fetchOptions);
        const surveyResult = await surveyRes.json();

        if (!surveyRes.ok) {
          setError(surveyResult.error || 'アンケート情報の取得に失敗しました。');
          return;
        }
        setSurveyData(surveyResult.data);

        if (!surveyResult.data.survey.allow_edit) {
          setError('このアンケートは編集が許可されていません。');
          return;
        }

        // 2. Fetch existing response
        const responseRes = await fetchWithSession(`/api/surveys/public/${public_id}/responses/${edit_token}`, {}, fetchOptions);
        const responseResult = await responseRes.json();

        if (!responseRes.ok) {
          if (responseRes.status === 403) {
            setError('この回答を編集する権限がありません。');
          } else if (responseRes.status === 404) {
            setError('回答が見つかりませんでした。');
          } else {
            setError(responseResult.error || '回答情報の取得に失敗しました。');
          }
          return;
        }
        setExistingResponse(responseResult.data);

      } catch (err) {
        setError('通信エラーが発生しました。');
      } finally {
        setIsLoading(false);
      }
    };

    fetchData();
  }, [public_id, edit_token, isLoggedIn, idToken]);

  const handleUpdateComplete = async (sender: Model) => {
    if (!idToken || !public_id || !edit_token) return;
    try {
      setIsSubmitting(true);
      setSubmitError(null);
      const response = await fetchWithSession(`/api/surveys/public/${public_id}/responses/${edit_token}`, {
        method: 'PUT',
        body: JSON.stringify({
          answer_json: sender.data,
        }),
      }, { onSessionRequired: identify });
      const result: SaveResponseResult = await response.json();

      if (!response.ok) {
        setSubmitError(result.error || '回答の更新に失敗しました。');
        return;
      }

      setIsSuccess(true);
      if (result.data) {
        setEmailStatus({
          sent: !!result.data.email_sent_at,
          error: result.data.email_error
        });
      }
    } catch (err) {
      setSubmitError('通信エラーが発生しました。');
    } finally {
      setIsSubmitting(false);
    }
  };

  if (isLoading) {
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
        <button
          onClick={() => navigate(`/s/${public_id}`)}
          style={{ marginTop: '1rem', padding: '0.5rem 1rem' }}
        >
          アンケートトップへ
        </button>
      </div>
    );
  }

  if (isSuccess) {
    return (
      <div style={{ padding: '2rem', textAlign: 'center', maxWidth: '600px', margin: '0 auto' }}>
        <h1 style={{ fontSize: '1.5rem', marginBottom: '1rem' }}>回答を更新しました</h1>
        <p style={{ marginBottom: '1rem' }}>ご協力ありがとうございました。</p>

        {emailStatus?.error ? (
          <p style={{ marginBottom: '1.5rem', color: '#dc3545' }}>
            ※修正控えメールの送信に失敗しました。回答の更新は完了しています。
          </p>
        ) : emailStatus?.sent ? (
          <p style={{ marginBottom: '1.5rem', color: '#28a745' }}>
            修正内容の控えをメールでお送りしました。ご確認ください。
          </p>
        ) : null}

        <button
          onClick={() => navigate(`/s/${public_id}`)}
          style={{ marginTop: '1rem', padding: '0.5rem 1rem' }}
        >
          アンケートトップへ
        </button>
      </div>
    );
  }

  return (
    <div style={{ padding: '1rem', maxWidth: '600px', margin: '0 auto' }}>
      <h1 style={{ fontSize: '1.5rem', marginBottom: '0.5rem' }}>回答の修正: {surveyData?.survey?.title}</h1>
      <p style={{ marginBottom: '1.5rem', color: '#666' }}>回答内容を修正して、再度送信してください。</p>

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

      {surveyData?.survey && existingResponse && (
        <SurveyRenderer
          questions={surveyData.survey.questions_json}
          data={existingResponse.answer_json}
          onComplete={handleUpdateComplete}
          isSubmitting={isSubmitting}
        />
      )}
    </div>
  );
};

export default EditResponsePage;
