import React from 'react';
import { Link, useLocation } from 'react-router-dom';

const Footer: React.FC = () => {
  const location = useLocation();
  const currentPath = encodeURIComponent(location.pathname + location.search);

  return (
    <footer style={{
      marginTop: '3rem',
      padding: '2rem 1rem',
      borderTop: '1px solid #eee',
      textAlign: 'center',
      backgroundColor: '#fdfdfd'
    }}>
      <nav>
        <ul style={{
          listStyle: 'none',
          padding: 0,
          margin: 0,
          display: 'flex',
          justifyContent: 'center',
          gap: '1.5rem',
          fontSize: '0.9rem'
        }}>
          <li>
            <Link to="/s" style={{ color: '#007bff', textDecoration: 'none' }}>
              回答履歴
            </Link>
          </li>
          <li>
            <Link
              to={`/respondent/edit?return_to=${currentPath}`}
              style={{ color: '#007bff', textDecoration: 'none' }}
            >
              本人情報編集
            </Link>
          </li>
        </ul>
      </nav>
      <div style={{ marginTop: '1rem', color: '#999', fontSize: '0.8rem' }}>
        &copy; {new Date().getFullYear()} Survey System
      </div>
    </footer>
  );
};

export default Footer;
