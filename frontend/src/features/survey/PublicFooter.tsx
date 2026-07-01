import React, { useEffect, useState } from 'react';
import { Link, useLocation } from 'react-router-dom';
import { useLiffContext } from '../liff/LiffContext';
import { getRespondentProfile } from '../../lib/publicApi';
import type { Respondent } from './types';

const PublicFooter: React.FC = () => {
  const location = useLocation();
  const { isLoggedIn, identify } = useLiffContext();
  const [respondent, setRespondent] = useState<Respondent | null>(null);
  const currentPath = encodeURIComponent(location.pathname + location.search);

  // Extract public_id from survey routes or search params
  const match = location.pathname.match(/^\/s\/([^/]+)/);
  const searchParams = new URLSearchParams(location.search);
  const public_id = (match ? match[1] : searchParams.get('public_id')) || undefined;

  useEffect(() => {
    if (isLoggedIn) {
      getRespondentProfile(() => identify(public_id))
        .then(setRespondent)
        .catch(() => {
          // Ignore error, respondent might not be identified yet
        });
    }
  }, [isLoggedIn, identify]);

  return (
    <footer className="public-footer">
      <div className="public-footer-inner">
        <div className="public-footer-top">
          <div className="public-footer-brand">アンケートフォーム</div>
          {respondent && (
            <div className="public-footer-user">
              {respondent.name} {respondent.honorific || 'さん'}
            </div>
          )}
        </div>

        <nav className="public-footer-nav">
          <ul className="public-footer-links">
            <li>
              <Link to={public_id ? `/s?public_id=${public_id}` : '/s'}>回答一覧</Link>
            </li>
            <li>
              <Link to={`/respondent/edit?return_to=${currentPath}${public_id ? `&public_id=${public_id}` : ''}`}>
                本人情報編集
              </Link>
            </li>
            <li>
              <Link to="/about-us">About Us</Link>
            </li>
          </ul>
        </nav>

        <div className="public-footer-copyright">
          &copy; {new Date().getFullYear()} アンケートフォーム
        </div>
      </div>
    </footer>
  );
};

export default PublicFooter;
