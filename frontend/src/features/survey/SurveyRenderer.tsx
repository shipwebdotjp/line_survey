import React, { useMemo, useEffect } from 'react';
import { Model } from 'survey-core';
import { Survey } from 'survey-react-ui';
import 'survey-core/survey-core.min.css';

interface SurveyRendererProps {
  questions: Record<string, any>;
  onComplete?: (sender: Model) => void;
  isSubmitting?: boolean;
}

const SurveyRenderer: React.FC<SurveyRendererProps> = ({ questions, onComplete, isSubmitting }) => {
  const survey = useMemo(() => {
    const model = new Model(questions);
    model.showCompletedPage = false;
    return model;
  }, [questions]);

  useEffect(() => {
    if (onComplete) {
      const wrappedOnComplete = (sender: Model) => {
        if (isSubmitting) return;
        onComplete(sender);
      };
      survey.onComplete.add(wrappedOnComplete);
      return () => {
        survey.onComplete.remove(wrappedOnComplete);
      };
    }
  }, [survey, onComplete, isSubmitting]);

  return (
    <div className={isSubmitting ? 'survey-submitting' : ''}>
      <Survey model={survey} />
      {isSubmitting && (
        <div style={{ textAlign: 'center', padding: '1rem', color: '#666' }}>
          送信中...
        </div>
      )}
    </div>
  );
};

export default SurveyRenderer;
