import React from 'react';
import { Link } from 'react-router-dom';
import type { LinkProps } from 'react-router-dom';

type BaseProps = {
  variant?: 'primary' | 'outline' | 'danger';
  size?: 'sm' | 'md';
  children: React.ReactNode;
  className?: string;
  title?: string;
  disabled?: boolean;
};

type ButtonProps = BaseProps &
  React.ButtonHTMLAttributes<HTMLButtonElement> & {
    to?: never;
    href?: never;
  };

type LinkButtonProps = BaseProps &
  Omit<LinkProps, 'className'> & {
    to: string;
    href?: never;
  };

type ExternalAnchorProps = BaseProps &
  Omit<React.AnchorHTMLAttributes<HTMLAnchorElement>, 'className'> & {
    href: string;
    to?: never;
  };

type AdminButtonProps = ButtonProps | LinkButtonProps | ExternalAnchorProps;

const AdminButton: React.FC<AdminButtonProps> = (props) => {
  const {
    variant = 'outline',
    size = 'md',
    children,
    className = '',
    ...rest
  } = props;

  const baseClass = 'admin-button';
  const variantClass = `admin-button-${variant}`;
  const sizeClass = size === 'sm' ? 'admin-button-sm' : '';
  const combinedClassName = `${baseClass} ${variantClass} ${sizeClass} ${className}`.trim();

  if (props.disabled) {
    return (
      <button className={combinedClassName} disabled title={props.title} type="button">
        {children}
      </button>
    );
  }

  if ('to' in rest && rest.to !== undefined) {
    const { to, ...linkProps } = rest as LinkButtonProps;
    return (
      <Link to={to} className={combinedClassName} {...linkProps}>
        {children}
      </Link>
    );
  }

  if ('href' in rest && rest.href !== undefined) {
    const { href, ...anchorProps } = rest as ExternalAnchorProps;
    return (
      <a href={href} className={combinedClassName} {...anchorProps}>
        {children}
      </a>
    );
  }

  const buttonProps = rest as ButtonProps;
  return (
    <button className={combinedClassName} {...buttonProps}>
      {children}
    </button>
  );
};

export default AdminButton;
