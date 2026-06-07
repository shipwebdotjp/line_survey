import React, { useEffect, useState } from 'react';
import { useParams } from 'react-router-dom';
import { isLiffEnabled } from '../../features/liff/liff-init';
import SurveyRenderer from '../../features/survey/SurveyRenderer';

const PublicSurveyPage: React.FC = () => {
  const { public_id } = useParams<{ public_id: string }>();
  const [error, setError] = useState<string | null>(null);

  useEffect(() => {
    if (!isLiffEnabled()) {
      setError('Please access this survey via LINE.');
    }
  }, []);

  if (error) {
    return (
      <div style={{ padding: '2rem', textAlign: 'center', color: 'red' }}>
        <h1>Error</h1>
        <p>{error}</p>
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
