import React, { useState, useRef, useEffect } from 'react';
import { Outlet, NavLink, useLocation, matchPath, Link, Navigate } from 'react-router-dom';
import { useAdminAuth } from '../../features/admin/auth/AdminAuthContext';

const AdminShell: React.FC = () => {
  const { user, isLoading, logout } = useAdminAuth();
  const { pathname, search } = useLocation();
  const [isMenuOpen, setIsMenuOpen] = useState(false);
  const menuRef = useRef<HTMLDivElement>(null);

  useEffect(() => {
    const handleClickOutside = (event: MouseEvent) => {
      if (menuRef.current && !menuRef.current.contains(event.target as Node)) {
        setIsMenuOpen(false);
      }
    };

    const handleEscape = (event: KeyboardEvent) => {
      if (event.key === 'Escape') {
        setIsMenuOpen(false);
      }
    };

    document.addEventListener('mousedown', handleClickOutside);
    document.addEventListener('keydown', handleEscape);
    return () => {
      document.removeEventListener('mousedown', handleClickOutside);
      document.removeEventListener('keydown', handleEscape);
    };
  }, []);

  useEffect(() => {
    setIsMenuOpen(false);
  }, [pathname]);

  if (isLoading) {
    return (
      <div style={{ padding: '2rem', textAlign: 'center' }}>
        <p>読み込み中...</p>
      </div>
    );
  }

  if (!user) {
    const from = pathname + search;
    return <Navigate to={`/admin/login?from=${encodeURIComponent(from)}`} replace />;
  }

  const getBreadcrumbs = () => {
    const breadcrumbs = [{ label: 'Admin', path: '/admin' }];

    if (matchPath('/admin/surveys/*', pathname) || pathname === '/admin/surveys') {
      breadcrumbs.push({ label: 'アンケート管理', path: '/admin/surveys' });

      const responseMatch = matchPath('/admin/surveys/:id/responses/:responseId/*', pathname) || matchPath('/admin/surveys/:id/responses/:responseId', pathname);
      const responsesMatch = matchPath('/admin/surveys/:id/responses', pathname);
      const summaryMatch = matchPath('/admin/surveys/:id/summary', pathname);
      const editMatch = matchPath('/admin/surveys/:id/edit', pathname);
      const newMatch = matchPath('/admin/surveys/new', pathname);

      if (responseMatch) {
        const { id, responseId } = responseMatch.params;
        breadcrumbs.push({ label: `回答一覧`, path: `/admin/surveys/${id}/responses` });
        breadcrumbs.push({ label: `回答詳細`, path: `/admin/surveys/${id}/responses/${responseId}` });
      } else if (responsesMatch) {
        breadcrumbs.push({ label: '回答一覧', path: pathname });
      } else if (summaryMatch) {
        breadcrumbs.push({ label: '要約', path: pathname });
      } else if (editMatch) {
        breadcrumbs.push({ label: '編集', path: pathname });
      } else if (newMatch) {
        breadcrumbs.push({ label: '新規作成', path: pathname });
      }
    } else if (matchPath('/admin/response-drafts/*', pathname) || pathname === '/admin/response-drafts') {
      breadcrumbs.push({ label: '下書き一覧', path: '/admin/response-drafts' });

      const detailMatch = matchPath('/admin/response-drafts/:id', pathname);

      if (detailMatch) {
        breadcrumbs.push({ label: '詳細', path: pathname });
      }
    } else if (matchPath('/admin/respondent-masters/*', pathname) || pathname === '/admin/respondent-masters') {
      breadcrumbs.push({ label: 'マスター管理', path: '/admin/respondent-masters' });

      const newMatch = matchPath('/admin/respondent-masters/new', pathname);
      const editMatch = matchPath('/admin/respondent-masters/:id/edit', pathname);

      if (newMatch) {
        breadcrumbs.push({ label: '新規登録', path: pathname });
      } else if (editMatch) {
        breadcrumbs.push({ label: '編集', path: pathname });
      }
    } else if (matchPath('/admin/respondents/*', pathname) || pathname === '/admin/respondents') {
      breadcrumbs.push({ label: '回答者管理', path: '/admin/respondents' });

      const detailMatch = matchPath('/admin/respondents/:id', pathname);
      const editMatch = matchPath('/admin/respondents/:id/edit', pathname);

      if (editMatch) {
        const { id } = editMatch.params;
        breadcrumbs.push({ label: '詳細', path: `/admin/respondents/${id}` });
        breadcrumbs.push({ label: '編集', path: pathname });
      } else if (detailMatch) {
        breadcrumbs.push({ label: '詳細', path: pathname });
      }
    } else if (pathname === '/admin/profile/edit') {
      breadcrumbs.push({ label: 'プロフィール編集', path: pathname });
    }

    return breadcrumbs;
  };

  const breadcrumbs = getBreadcrumbs();

  const showSurveySubNav = matchPath('/admin/surveys/*', pathname) ||
    pathname === '/admin/surveys' ||
    matchPath('/admin/response-drafts/*', pathname) ||
    pathname === '/admin/response-drafts';

  return (
    <div className="admin-layout">
      <aside className="admin-sidebar">
        <div className="admin-sidebar-brand">
          Admin Panel
        </div>
        <nav className="admin-nav">
          <ul>
            <li>
              <NavLink
                to="/admin/surveys"
                className={({ isActive }) => isActive ? 'active' : ''}
              >
                アンケート管理
              </NavLink>
              {showSurveySubNav && (
                <ul className="admin-nav-sub">
                  <li>
                    <NavLink
                      to="/admin/surveys"
                      className={({ isActive }) => isActive ? 'active' : ''}
                      end
                    >
                      アンケート一覧
                    </NavLink>
                  </li>
                  <li>
                    <NavLink
                      to="/admin/response-drafts"
                      className={({ isActive }) => isActive ? 'active' : ''}
                    >
                      下書き一覧
                    </NavLink>
                  </li>
                </ul>
              )}
            </li>
            <li>
              <NavLink
                to="/admin/respondent-masters"
                className={({ isActive }) => isActive ? 'active' : ''}
              >
                マスター管理
              </NavLink>
            </li>
            <li>
              <NavLink
                to="/admin/respondents"
                className={({ isActive }) => isActive ? 'active' : ''}
              >
                回答者管理
              </NavLink>
            </li>
          </ul>
        </nav>
      </aside>
      <div className="admin-main-container">
        <header className="admin-header">
          <div className="admin-breadcrumb">
            {breadcrumbs.map((bc, index) => (
              <React.Fragment key={bc.path}>
                {index > 0 && <span className="separator"> / </span>}
                {index === breadcrumbs.length - 1 ? (
                  <span>{bc.label}</span>
                ) : (
                  <Link to={bc.path}>{bc.label}</Link>
                )}
              </React.Fragment>
            ))}
          </div>
          <div className="admin-header-user" ref={menuRef}>
            {user && (
              <>
                <button
                  className={`admin-user-menu-trigger ${isMenuOpen ? 'active' : ''}`}
                  onClick={() => setIsMenuOpen(!isMenuOpen)}
                  aria-expanded={isMenuOpen}
                  aria-haspopup="true"
                >
                  <span>{user.line_display_name || `User (ID:${user.id})`}</span>
                  <svg className="chevron" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round">
                    <polyline points="6 9 12 15 18 9"></polyline>
                  </svg>
                </button>

                {isMenuOpen && (
                  <div className="admin-dropdown-menu">
                    <Link to="/admin/profile/edit" className="admin-dropdown-item">
                      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round">
                        <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path>
                        <circle cx="12" cy="7" r="4"></circle>
                      </svg>
                      プロフィール編集
                    </Link>
                    <div className="admin-dropdown-divider"></div>
                    <button onClick={logout} className="admin-dropdown-item danger">
                      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round">
                        <path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"></path>
                        <polyline points="16 17 21 12 16 7"></polyline>
                        <line x1="21" y1="12" x2="9" y2="12"></line>
                      </svg>
                      ログアウト
                    </button>
                  </div>
                )}
              </>
            )}
          </div>
        </header>
        <main className="admin-content">
          <Outlet />
        </main>
      </div>
    </div>
  );
};

export default AdminShell;
