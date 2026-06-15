import React, { useEffect, useState } from 'react';
import { useParams } from 'react-router-dom';
import { adminSurveyApi } from '../../features/admin/surveys/adminSurveyApi';
import type { Survey, SurveySummary, QuestionSummary } from '../../features/admin/surveys/types';
import AdminButton from '../../components/admin/AdminButton';
import SurveyResultsNav from '../../features/admin/surveys/SurveyResultsNav';
import {
  PieChart,
  Pie,
  Cell,
  ResponsiveContainer,
  BarChart,
  Bar,
  XAxis,
  YAxis,
  CartesianGrid,
  Tooltip,
} from 'recharts';

const COLORS = ['#4f46e5', '#10b981', '#f59e0b', '#ef4444', '#8b5cf6', '#ec4899'];

const SummaryTextCard: React.FC<{ question: QuestionSummary }> = ({ question }) => (
  <div className="admin-card mb-4">
    <div className="admin-card-header">
      <h3>{question.title}</h3>
    </div>
    <div className="admin-card-body">
      <div className="mb-4">
        <span className="badge badge-draft">{question.answeredCount}件の回答</span>
        {question.emptyCount > 0 && (
          <span className="badge badge-draft ml-2" style={{ marginLeft: '8px' }}>未回答: {question.emptyCount}</span>
        )}
      </div>
      <div style={{ maxHeight: '300px', overflowY: 'auto', border: '1px solid #e5e7eb', borderRadius: '4px' }}>
        {question.answers && question.answers.length > 0 ? (
          <ul style={{ listStyle: 'none', padding: 0, margin: 0 }}>
            {question.answers.map((ans: string, idx: number) => (
              <li key={idx} style={{ padding: '0.75rem', borderBottom: idx === question.answers.length - 1 ? 'none' : '1px solid #e5e7eb' }}>
                {ans}
              </li>
            ))}
          </ul>
        ) : (
          <p style={{ padding: '1rem', color: '#6b7280' }}>回答はありません</p>
        )}
      </div>
    </div>
  </div>
);

const SummaryPieChart: React.FC<{ question: QuestionSummary }> = ({ question }) => {
  const data = question.choices || [];

  return (
    <div className="admin-card mb-4">
      <div className="admin-card-header">
        <h3>{question.title}</h3>
      </div>
      <div className="admin-card-body">
        <div className="mb-4">
          <span className="badge badge-draft">対象者: {question.targetCount}</span>
        </div>
        <div style={{ display: 'flex', flexWrap: 'wrap', alignItems: 'center' }}>
          <div style={{ width: '100%', maxWidth: '300px', height: '250px' }}>
            <ResponsiveContainer width="100%" height="100%">
              <PieChart>
                <Pie
                  data={data}
                  cx="50%"
                  cy="50%"
                  innerRadius={60}
                  outerRadius={80}
                  paddingAngle={5}
                  dataKey="count"
                  isAnimationActive={false}
                >
                  {data.map((_: any, index: number) => (
                    <Cell key={`cell-${index}`} fill={COLORS[index % COLORS.length]} />
                  ))}
                </Pie>
                <Tooltip />
              </PieChart>
            </ResponsiveContainer>
          </div>
          <div style={{ flex: 1, minWidth: '200px' }}>
            <table className="admin-table" style={{ border: 'none' }}>
              <tbody>
                {data.map((choice: any, index: number) => (
                  <tr key={index} style={{ border: 'none' }}>
                    <td style={{ border: 'none', padding: '0.5rem' }}>
                      <span style={{ display: 'inline-block', width: '12px', height: '12px', backgroundColor: COLORS[index % COLORS.length], marginRight: '8px', borderRadius: '2px' }}></span>
                      {choice.label}
                    </td>
                    <td style={{ border: 'none', padding: '0.5rem', textAlign: 'right' }}>{choice.count}件</td>
                    <td style={{ border: 'none', padding: '0.5rem', textAlign: 'right' }}>{choice.rate}%</td>
                  </tr>
                ))}
              </tbody>
            </table>
          </div>
        </div>
      </div>
    </div>
  );
};

const SummaryBarChart: React.FC<{ question: QuestionSummary }> = ({ question }) => {
  const data = question.choices || [];

  return (
    <div className="admin-card mb-4">
      <div className="admin-card-header">
        <h3>{question.title}</h3>
      </div>
      <div className="admin-card-body">
        <div className="mb-4">
          <span className="badge badge-draft">対象者: {question.targetCount}</span>
          {question.type !== 'checkbox' && (
            <>
              <span className="badge badge-draft ml-2" style={{ marginLeft: '8px' }}>回答済み: {question.answeredCount}</span>
              <span className="badge badge-draft ml-2" style={{ marginLeft: '8px' }}>未回答: {question.emptyCount}</span>
            </>
          )}
        </div>
        <div style={{ width: '100%', height: Math.max(200, data.length * 40 + 60) + 'px' }}>
          <ResponsiveContainer width="100%" height="100%">
            <BarChart
              data={data}
              layout="vertical"
              margin={{ top: 5, right: 30, left: 100, bottom: 5 }}
            >
              <Bar dataKey="count" fill="#4f46e5" radius={[0, 4, 4, 0]} isAnimationActive={false} />
              <CartesianGrid strokeDasharray="3 3" horizontal={true} vertical={false} />
              <XAxis type="number" hide />
              <YAxis
                type="category"
                dataKey="label"
                width={90}
                tick={{ fontSize: 12 }}
              />
              <Tooltip formatter={(value) => [`${value}件`]} />
            </BarChart>
          </ResponsiveContainer>
        </div>
        <div className="mt-4">
          <table className="admin-table" style={{ border: 'none' }}>
            <thead>
              <tr>
                <th style={{ background: 'none' }}>選択肢</th>
                <th style={{ background: 'none', textAlign: 'right' }}>件数</th>
                <th style={{ background: 'none', textAlign: 'right' }}>割合</th>
              </tr>
            </thead>
            <tbody>
              {data.map((choice: any, index: number) => (
                <tr key={index}>
                  <td>{choice.label}</td>
                  <td style={{ textAlign: 'right' }}>{choice.count}</td>
                  <td style={{ textAlign: 'right' }}>{choice.rate}%</td>
                </tr>
              ))}
            </tbody>
          </table>
        </div>
      </div>
    </div>
  );
};

const SurveySummaryPage: React.FC = () => {
  const { id } = useParams<{ id: string }>();
  const surveyId = Number(id);

  const [survey, setSurvey] = useState<Survey | null>(null);
  const [summary, setSummary] = useState<SurveySummary | null>(null);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState<string | null>(null);

  useEffect(() => {
    const fetchData = async () => {
      if (Number.isNaN(surveyId)) {
        setError('無効なパラメータです。');
        setLoading(false);
        return;
      }

      try {
        setLoading(true);
        const [surveyData, summaryData] = await Promise.all([
          adminSurveyApi.get(surveyId),
          adminSurveyApi.getSummary(surveyId),
        ]);
        setSurvey(surveyData);
        setSummary(summaryData);
        setError(null);
      } catch (err) {
        setError('データの取得に失敗しました。');
        console.error(err);
      } finally {
        setLoading(false);
      }
    };

    fetchData();
  }, [surveyId]);

  if (loading) {
    return (
      <div className="loading-container">
        <p>読み込み中...</p>
      </div>
    );
  }

  if (error || !survey || !summary) {
    return (
      <div className="error-container">
        <p>{error || 'アンケートが見つかりませんでした。'}</p>
        <AdminButton to="/admin/surveys">アンケート一覧に戻る</AdminButton>
      </div>
    );
  }

  return (
    <div>
      <div className="admin-page-header">
        <h1>{survey.title} - 集計要約</h1>
        <div className="admin-actions">
          <AdminButton to="/admin/surveys">アンケート一覧に戻る</AdminButton>
        </div>
      </div>

      <SurveyResultsNav surveyId={surveyId} />

      <div className="mb-8">
        <div className="admin-card" style={{ display: 'inline-block', padding: '1rem 2rem' }}>
          <div style={{ fontSize: '0.875rem', color: '#6b7280' }}>総回答数</div>
          <div style={{ fontSize: '2rem', fontWeight: 'bold', color: '#111827' }}>{summary.totalResponses}</div>
        </div>
      </div>

      <div>
        {summary.questions.map((q, idx) => {
          if (q.type === 'text' || q.type === 'comment') {
            return <SummaryTextCard key={idx} question={q} />;
          }
          if (q.type === 'boolean') {
            return <SummaryPieChart key={idx} question={q} />;
          }
          if (['checkbox', 'radiogroup', 'dropdown'].includes(q.type)) {
            return <SummaryBarChart key={idx} question={q} />;
          }
          if (q.type === 'unsupported') {
            return (
              <div key={idx} className="admin-card mb-4">
                <div className="admin-card-header">
                  <h3>{q.title}</h3>
                </div>
                <div className="admin-card-body">
                  <p style={{ color: '#6b7280' }}>未対応の設問タイプです ({q.type})</p>
                </div>
              </div>
            );
          }
          return null;
        })}
      </div>
    </div>
  );
};

export default SurveySummaryPage;
