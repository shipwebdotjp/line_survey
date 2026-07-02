import React from 'react';
import { NavLink } from 'react-router-dom';

interface SurveyResultsNavProps {
  surveyId: number;
}

const SurveyResultsNav: React.FC<SurveyResultsNavProps> = ({ surveyId }) => {
  return (
    <div className="admin-tabs-container mb-4">
      <nav className="admin-tabs">
        <NavLink
          to={`/manage/surveys/${surveyId}/responses`}
          className={({ isActive }) => (isActive ? 'admin-tab active' : 'admin-tab')}
          end
        >
          回答一覧
        </NavLink>
        <NavLink
          to={`/manage/surveys/${surveyId}/summary`}
          className={({ isActive }) => (isActive ? 'admin-tab active' : 'admin-tab')}
        >
          要約
        </NavLink>
      </nav>
    </div>
  );
};

export default SurveyResultsNav;
