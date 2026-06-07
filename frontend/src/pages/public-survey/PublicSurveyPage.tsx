import React, { useEffect, useState } from 'react';
import { useParams } from 'react-router-dom';
import { useLiff } from '../../features/liff/useLiff';
import LiffError from '../../features/liff/LiffError';
import SurveyRenderer from '../../features/survey/SurveyRenderer';

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
  const { isInitialized, isLoggedIn, error: liffError } = useLiff();
  const [surveyData, setSurveyData] = useState<SurveyData | null>(null);
  const [isLoading, setIsLoading] = useState(true);
  const [error, setError] = useState<string | null>(null);

  useEffect(() => {
    if (!isInitialized || !isLoggedIn || !public_id) return;

    const fetchSurvey = async () => {
      try {
        const response = await fetch(`/api/surveys/public/${public_id}`);

        let result;
        try {
          result = await response.json();
        } catch (jsonErr) {
          setError('レスポンスの解析中にエラーが発生しました。');
          setIsLoading(false);
          return;
        }

        if (!response.ok) {
          if (response.status === 404) {
            setError('アンケートが見つかりませんでした。');
          } else {
            setError(result.error || '予期せぬエラーが発生しました。');
          }
          return;
        }

        setSurveyData(result.data);
      } catch (err) {
        setError('通信エラーが発生しました。');
      } finally {
        setIsLoading(false);
      }
    };

    fetchSurvey();
  }, [isInitialized, isLoggedIn, public_id]);

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

  return (
    <div style={{ padding: '1rem' }}>
      <h1 style={{ fontSize: '1.5rem', marginBottom: '0.5rem' }}>{surveyData.survey.title}</h1>
      {surveyData.survey.description && (
        <p style={{ marginBottom: '1.5rem', color: '#666' }}>{surveyData.survey.description}</p>
      )}
      <SurveyRenderer
        questions={surveyData.survey.questions_json}
        onComplete={(sender) => console.log('Survey complete:', sender.data)}
      />
    </div>
  );
};

export default PublicSurveyPage;
