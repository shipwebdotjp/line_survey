import React, { useMemo, useEffect, useLayoutEffect, useRef } from 'react';
import { Model } from 'survey-core';
import { Survey } from 'survey-react-ui';
import 'survey-core/survey-core.min.css';

interface SurveyRendererProps {
  questions: Record<string, any>;
  onComplete?: (sender: Model) => void;
  onValueChanged?: (sender: Model, options: any) => void;
  isSubmitting?: boolean;
  initialValues?: Record<string, any>;
  readOnly?: boolean;
  isPublic?: boolean;
}

const SurveyRenderer: React.FC<SurveyRendererProps> = ({
  questions,
  onComplete,
  onValueChanged,
  isSubmitting = false,
  initialValues,
  readOnly = false,
  isPublic = false,
}) => {
  const survey = useMemo(() => {
    const model = new Model(questions);
    model.showCompletedPage = false;
    // 送信ボタンのラベル
    model.completeText = '回答を送信する';
    model.pageNextText = '次へ';
    model.pagePrevText = '戻る';
    if (readOnly) {
      model.mode = 'display';
    }
    return model;
  }, [questions, readOnly]);

  const appliedInitialValuesRef = useRef<{ survey: Model | null; signature: string }>({
    survey: null,
    signature: '',
  });

  useLayoutEffect(() => {
    const signature = JSON.stringify(initialValues ?? null);
    const alreadyApplied =
      appliedInitialValuesRef.current.survey === survey &&
      appliedInitialValuesRef.current.signature === signature;

    if (alreadyApplied) {
      return;
    }

    if (initialValues) {
      Object.entries(initialValues).forEach(([name, value]) => {
        if (value === undefined) {
          return;
        }

        if (survey.getQuestionByName(name)) {
          survey.setValue(name, value, false, false);
        }
      });
    }

    appliedInitialValuesRef.current = {
      survey,
      signature,
    };
  }, [survey, initialValues]);

  const isSubmittingRef = useRef(isSubmitting);
  useEffect(() => {
    isSubmittingRef.current = isSubmitting;
  }, [isSubmitting]);

  useEffect(() => {
    if (onValueChanged) {
      survey.onValueChanged.add(onValueChanged);
      return () => {
        survey.onValueChanged.remove(onValueChanged);
      };
    }
  }, [survey, onValueChanged]);

  useEffect(() => {
    if (onComplete) {
      const wrappedOnComplete = (sender: Model) => {
        if (isSubmittingRef.current) return;
        onComplete(sender);
      };
      survey.onComplete.add(wrappedOnComplete);
      return () => {
        survey.onComplete.remove(wrappedOnComplete);
      };
    }
  }, [survey, onComplete]);

  const containerClassName = [
    isSubmitting ? 'survey-submitting' : '',
    isPublic ? 'public-survey-renderer' : ''
  ].filter(Boolean).join(' ');

  return (
    <div
      className={containerClassName}
      style={{ position: 'relative' }}
      aria-busy={isSubmitting}
    >
      <Survey model={survey} />
      {isSubmitting && (
        <div style={{
          position: 'absolute',
          top: 0,
          left: 0,
          right: 0,
          bottom: 0,
          backgroundColor: 'rgba(255, 255, 255, 0.7)',
          display: 'flex',
          flexDirection: 'column',
          alignItems: 'center',
          justifyContent: 'center',
          zIndex: 1000,
          pointerEvents: 'all'
        }}>
          <div style={{
            width: '40px',
            height: '40px',
            border: '4px solid #f3f3f3',
            borderTop: '4px solid #3498db',
            borderRadius: '50%',
            animation: 'spin 1s linear infinite',
            marginBottom: '10px'
          }} />
          <div style={{ color: '#333', fontWeight: 'bold' }}>送信中...</div>
          <style>{`
            @keyframes spin {
              0% { transform: rotate(0deg); }
              100% { transform: rotate(360deg); }
            }
          `}</style>
        </div>
      )}
    </div>
  );
};

export default SurveyRenderer;
