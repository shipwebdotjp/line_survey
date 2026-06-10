import React from 'react';
import { useNavigate } from 'react-router-dom';
import SurveyForm from '../../features/admin/surveys/SurveyForm';
import { adminSurveyApi } from '../../features/admin/surveys/adminSurveyApi';
import type { SurveyCreateParams } from '../../features/admin/surveys/types';

const SurveyCreatePage: React.FC = () => {
  const navigate = useNavigate();

  const handleSubmit = async (values: SurveyCreateParams) => {
    await adminSurveyApi.create(values);
    navigate('/admin/surveys');
  };

  const handleCancel = () => {
    navigate('/admin/surveys');
  };

  return (
    <div>
      <div className="admin-page-header">
        <h1>アンケート新規作成</h1>
      </div>
      <SurveyForm
        onSubmit={handleSubmit}
        onCancel={handleCancel}
        submitLabel="作成"
      />
    </div>
  );
};

export default SurveyCreatePage;
