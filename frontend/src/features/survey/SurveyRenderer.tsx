import React from 'react';
import { Model } from 'survey-core';
import { Survey } from 'survey-react-ui';
import 'survey-core/survey-core.min.css';

interface SurveyRendererProps {
  questions: any;
  onComplete?: (sender: Model) => void;
}

const SurveyRenderer: React.FC<SurveyRendererProps> = ({ questions, onComplete }) => {
  const survey = new Model(questions);

  if (onComplete) {
    survey.onComplete.add(onComplete);
  }

  return <Survey model={survey} />;
};

export default SurveyRenderer;
