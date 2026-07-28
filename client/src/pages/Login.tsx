import { useState, type FC, type FormEvent } from 'react';
import { Link, useNavigate } from 'react-router-dom';
import AuthLayout from '@/components/auth/AuthLayout';
import TextField from '@/components/auth/TextField';
import PasswordField from '@/components/auth/PasswordField';
import OAuthRow from '@/components/auth/OAuth';
import Icon from '@/components/Icon';
import type { FormErrors, LoginFormState } from '@/integrations/types';

const Login: FC = () => {
  const navigate = useNavigate();
  const [form, setForm] = useState<LoginFormState>({ email: '', password: '', remember: false });
  const [errors, setErrors] = useState<FormErrors>({});
  const [submitting, setSubmitting] = useState(false);
  const appName = import.meta.env.VITE_APP_NAME;

  const validate = (): boolean => {
    const next: FormErrors = {};
    if (!form.email) next.email = 'Enter your email address.';
    else if (!/^\S+@\S+\.\S+$/.test(form.email)) next.email = 'Enter a valid email address.';
    if (!form.password) next.password = 'Enter your password.';
    setErrors(next);
    return Object.keys(next).length === 0;
  };

  const handleSubmit = async (e: FormEvent) => {
    e.preventDefault();
    if (!validate()) return;
    setSubmitting(true);
    // Replace with real auth call
    await new Promise((r) => setTimeout(r, 900));
    setSubmitting(false);
    navigate('/');
  };

  return (
    <AuthLayout>
      <div className="auth-form-header">
        <div className="auth-form-title">Welcome back</div>
        <div className="auth-form-sub">
          New to {appName}? <Link to="/register">Create an account</Link>
        </div>
      </div>

      <form onSubmit={handleSubmit} noValidate>
        <TextField
          label="Email address"
          type="email"
          icon="mail"
          placeholder="you@example.com"
          autoComplete="email"
          value={form.email}
          onChange={(e) => setForm((f) => ({ ...f, email: e.target.value }))}
          error={errors.email}
        />

        <PasswordField
          label="Password"
          placeholder="Enter your password"
          autoComplete="current-password"
          value={form.password}
          onChange={(e) => setForm((f) => ({ ...f, password: e.target.value }))}
          error={errors.password}
        />

        <div className="field-row">
          <label className="checkbox-row">
            <input
              type="checkbox"
              className="checkbox-input"
              checked={form.remember}
              onChange={(e) => setForm((f) => ({ ...f, remember: e.target.checked }))}
            />
            Remember me
          </label>
          <Link to="/forgot-password" className="forgot-link">
            Forgot password?
          </Link>
        </div>

        <button type="submit" className="auth-submit-btn" disabled={submitting}>
          {submitting ? 'Signing in…' : 'Log in'}
          {!submitting && <Icon name="arrow-right" strokeWidth={2.5} />}
        </button>
      </form>

      <div className="auth-divider">or continue with</div>
      <OAuthRow />
    </AuthLayout>
  );
};

export default Login;
