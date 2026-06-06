import React from 'react';
import { Model } from 'survey-core';
import { Survey } from 'survey-react-ui';
import 'survey-core/survey-core.min.css';
import questions from './questions.json';

interface SurveyRendererProps {
  onComplete?: (sender: Model) => void;
}

const SurveyRenderer: React.FC<SurveyRendererProps> = ({ onComplete }) => {
  const survey = new Model(questions);

  if (onComplete) {
    survey.onComplete.add(onComplete);
  }

  return <Survey model={survey} />;
};

export default SurveyRenderer;
