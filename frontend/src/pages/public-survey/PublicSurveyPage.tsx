import React, { useEffect, useState } from 'react';
import { useParams } from 'react-router-dom';
import { useLiff } from '../../features/liff/useLiff';
import LiffError from '../../features/liff/LiffError';
import SurveyRenderer from '../../features/survey/SurveyRenderer';
import RespondentIdentification from '../../features/survey/RespondentIdentification';
import type { IdentifyStatus, Respondent, IdentifyResponse } from '../../features/survey/types';

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
  const { isInitialized, isLoggedIn, idToken, error: liffError } = useLiff();
  const [surveyData, setSurveyData] = useState<SurveyData | null>(null);
  const [identifyStatus, setIdentifyStatus] = useState<IdentifyStatus | null>(null);
  const [respondent, setRespondent] = useState<Respondent | null>(null);
  const [isLoading, setIsLoading] = useState(true);
  const [isIdentifying, setIsIdentifying] = useState(false);
  const [error, setError] = useState<string | null>(null);
  const [identifyError, setIdentifyError] = useState<string | null>(null);

  useEffect(() => {
    if (!isInitialized || !isLoggedIn || !public_id || !idToken) return;

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
  }, [isInitialized, isLoggedIn, public_id, idToken]);

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

  if (liffError) {
    return <LiffError error={liffError} />;
  }

  if (!isInitialized || !isLoggedIn || (isLoading && !error)) {
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
        <SurveyRenderer
          questions={surveyData.survey?.questions_json}
          onComplete={(sender) => console.log('Survey complete:', sender.data)}
        />
      )}
    </div>
  );
};

export default PublicSurveyPage;
