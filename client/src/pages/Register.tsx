import { useState, type FC, type FormEvent } from 'react';
import { Link, useNavigate } from 'react-router-dom';
import AuthLayout from '@/components/auth/AuthLayout';
import TextField from '@/components/auth/TextField';
import PasswordField from '@/components/auth/PasswordField';
import OAuthRow from '@/components/auth/OAuth';
import Icon from '@/components/Icon';
import type { FormErrors, FormState } from '@/types';

const Register: FC = () => {
  const navigate = useNavigate();
  const [form, setForm] = useState<FormState>({ name: '', email: '', password: '', agree: false });
  const [errors, setErrors] = useState<FormErrors>({});
  const [submitting, setSubmitting] = useState(false);

  const validate = (): boolean => {
    const next: FormErrors = {};
    if (!form.name.trim()) next.name = 'Enter your full name.';
    if (!form.email) next.email = 'Enter your email address.';
    else if (!/^\S+@\S+\.\S+$/.test(form.email)) next.email = 'Enter a valid email address.';
    if (!form.password) next.password = 'Create a password.';
    else if (form.password.length < 8) next.password = 'Use at least 8 characters.';
    if (!form.agree) next.agree = 'You need to accept the terms to continue.';
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
        <div className="auth-form-title">Create your account</div>
        <div className="auth-form-sub">
          Already banking with us? <Link to="/login">Log in</Link>
        </div>
      </div>

      <form onSubmit={handleSubmit} noValidate>
        <TextField
          label="Full name"
          type="text"
          icon="user"
          placeholder="Joy Adeyemi"
          autoComplete="name"
          value={form.name}
          onChange={(e) => setForm((f) => ({ ...f, name: e.target.value }))}
          error={errors.name}
        />

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
          placeholder="At least 8 characters"
          autoComplete="new-password"
          showStrength
          value={form.password}
          onChange={(e) => setForm((f) => ({ ...f, password: e.target.value }))}
          error={errors.password}
        />

        <div className="field" style={{ marginBottom: 22 }}>
          <label className="checkbox-row">
            <input
              type="checkbox"
              className="checkbox-input"
              checked={form.agree}
              onChange={(e) => setForm((f) => ({ ...f, agree: e.target.checked }))}
            />
            <span>
              I agree to Kori's <a href="#">Terms of Service</a> and <a href="#">Privacy Policy</a>.
            </span>
          </label>
          {errors.agree && <div className="field-error">{errors.agree}</div>}
        </div>

        <button type="submit" className="auth-submit-btn" disabled={submitting}>
          {submitting ? 'Creating account…' : 'Create account'}
          {!submitting && <Icon name="shield" strokeWidth={2} />}
        </button>
      </form>

      <div className="auth-divider">or continue with</div>
      <OAuthRow />
    </AuthLayout>
  );
};

export default Register;
