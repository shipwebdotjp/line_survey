import React from 'react';
import { Link } from 'react-router-dom';

const PublicHeader: React.FC = () => {
  return (
    <header className="public-header">
      <div className="public-header-inner">
        <Link to="/" className="public-header-brand">
          Survey System
        </Link>
      </div>
    </header>
  );
};

export default PublicHeader;
