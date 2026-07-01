import React, { useEffect, useState } from 'react';
import { useParams, useNavigate } from 'react-router-dom';
import { useLiffContext } from '../../features/liff/LiffContext';
import { fetchWithSession } from '../../lib/publicApi';
import SurveyRenderer from '../../features/survey/SurveyRenderer';
import { formatHistoryDate } from '../../lib/dateUtils';
import type { SurveyResponse, SurveyData } from '../../features/survey/types';

const ShowResponsePage: React.FC = () => {
  const { public_id, edit_token } = useParams<{ public_id: string, edit_token: string }>();
  const { isLoggedIn, idToken, identify } = useLiffContext();
  const navigate = useNavigate();

  const [surveyData, setSurveyData] = useState<SurveyData | null>(null);
  const [existingResponse, setExistingResponse] = useState<SurveyResponse | null>(null);
  const [isLoading, setIsLoading] = useState(true);
  const [error, setError] = useState<string | null>(null);
  const canEditResponse = !!surveyData?.survey?.allow_edit && !!surveyData?.can_answer;

  useEffect(() => {
    if (isLoading && !error) {
      document.title = '読み込み中...';
    } else if (error) {
      document.title = 'エラー';
    } else if (surveyData) {
      document.title = `回答内容: ${surveyData.survey?.title || ''}`;
    }
  }, [isLoading, error, surveyData]);

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

        // 2. Fetch existing response
        const responseRes = await fetchWithSession(`/api/surveys/public/${public_id}/responses/${edit_token}`, {}, fetchOptions);
        const responseResult = await responseRes.json();

        if (!responseRes.ok) {
          if (responseRes.status === 403) {
            setError('この回答を閲覧する権限がありません。');
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

  if (isLoading) {
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
          <div style={{ marginTop: '1.5rem', display: 'flex', justifyContent: 'center', gap: '1rem' }}>
            <button
              onClick={() => navigate('/s')}
              className="public-btn public-btn-secondary"
            >
              回答履歴へ
            </button>
            <button
              onClick={() => navigate(`/s/${public_id}`)}
              className="public-btn public-btn-primary"
            >
              アンケートトップへ
            </button>
          </div>
        </div>
      </div>
    );
  }

  return (
    <div className="public-container">
      <div className="public-card">
        <h1 style={{ fontSize: '1.5rem', marginBottom: '0.5rem' }}>{surveyData?.survey?.title}</h1>
        <p style={{ marginBottom: '0.25rem', color: '#6b7280' }}>回答内容</p>

        <div style={{ display: 'flex', flexWrap: 'wrap', gap: '0.25rem 1rem', fontSize: '0.8rem', color: '#6b7280', marginBottom: '1rem' }}>
          {existingResponse && (
            <>
              <div>
                回答日時: <time dateTime={existingResponse.submitted_at}>{formatHistoryDate(existingResponse.submitted_at)}</time>
              </div>
              {existingResponse.updated_at !== existingResponse.submitted_at && (
                <div>
                  更新日時: <time dateTime={existingResponse.updated_at}>{formatHistoryDate(existingResponse.updated_at)}</time>
                </div>
              )}
            </>
          )}
        </div>

        <div style={{ border: '1px solid #e5e7eb', borderRadius: '8px', padding: '1rem', marginBottom: '1.5rem', backgroundColor: '#fff' }}>
          {surveyData?.survey && existingResponse && (
            <SurveyRenderer
              questions={existingResponse.survey_snapshot_json || surveyData.survey.questions_json}
              initialValues={existingResponse.answer_json}
              readOnly={true}
            />
          )}
        </div>

        <div style={{ display: 'flex', flexDirection: 'column', gap: '1rem', alignItems: 'center' }}>
          {canEditResponse && (
            <button
              onClick={() => navigate(`/s/${public_id}/r/${edit_token}/edit`)}
              className="public-btn public-btn-primary public-btn-full"
            >
              回答を修正する
            </button>
          )}

          <button
            onClick={() => navigate('/s')}
            className="public-btn public-btn-secondary public-btn-full"
          >
            回答履歴に戻る
          </button>
        </div>
      </div>
    </div>
  );
};

export default ShowResponsePage;
