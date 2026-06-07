import React from 'react';
import { useParams } from 'react-router-dom';
import { useLiff } from '../../features/liff/useLiff';
import LiffError from '../../features/liff/LiffError';
import SurveyRenderer from '../../features/survey/SurveyRenderer';

const PublicSurveyPage: React.FC = () => {
  const { public_id } = useParams<{ public_id: string }>();
  const { isInitialized, isLoggedIn, error } = useLiff();

  if (error) {
    return <LiffError error={error} />;
  }

  if (!isInitialized || !isLoggedIn) {
    return (
      <div style={{ padding: '2rem', textAlign: 'center' }}>
        <p>読み込み中...</p>
      </div>
    );
  }

  return (
    <div>
      <h1>Survey: {public_id}</h1>
      <SurveyRenderer onComplete={(sender) => console.log(sender.data)} />
    </div>
  );
};

export default PublicSurveyPage;
