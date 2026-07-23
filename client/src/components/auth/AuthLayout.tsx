import type { FC, ReactNode } from 'react';
import AuthBrandPanel from '@/components/auth/AuthBrandPanel';

interface AuthLayoutProps {
  children: ReactNode;
}

const AuthLayout: FC<AuthLayoutProps> = ({ children }) => {
  return (
    <div className="auth-page">
      <AuthBrandPanel />
      <div className="auth-form-panel">
        <div className="auth-form-wrap">{children}</div>
      </div>
    </div>
  );
};

export default AuthLayout;
