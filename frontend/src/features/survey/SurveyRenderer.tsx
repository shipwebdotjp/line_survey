import React, { useMemo, useEffect } from 'react';
import { Model } from 'survey-core';
import { Survey } from 'survey-react-ui';
import 'survey-core/survey-core.min.css';

interface SurveyRendererProps {
  questions: Record<string, any>;
  onComplete?: (sender: Model) => void;
}

const SurveyRenderer: React.FC<SurveyRendererProps> = ({ questions, onComplete }) => {
  const survey = useMemo(() => new Model(questions), [questions]);

  useEffect(() => {
    if (onComplete) {
      survey.onComplete.add(onComplete);
      return () => {
        survey.onComplete.remove(onComplete);
      };
    }
  }, [survey, onComplete]);

  return <Survey model={survey} />;
};

export default SurveyRenderer;
