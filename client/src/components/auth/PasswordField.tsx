import { useState, type FC, type InputHTMLAttributes } from 'react';
import Icon from '../Icon';

interface PasswordFieldProps extends InputHTMLAttributes<HTMLInputElement> {
  label: string;
  error?: string;
  hint?: string;
  showStrength?: boolean;
}

function getStrength(value: string): { level: 0 | 1 | 2 | 3; label: string } {
  if (!value) return { level: 0, label: '' };
  let score = 0;
  if (value.length >= 8) score++;
  if (/[A-Z]/.test(value) && /[a-z]/.test(value)) score++;
  if (/[0-9]/.test(value) && /[^A-Za-z0-9]/.test(value)) score++;

  if (score <= 1) return { level: 1, label: 'Weak' };
  if (score === 2) return { level: 2, label: 'Good' };
  return { level: 3, label: 'Strong' };
}

const PasswordField: FC<PasswordFieldProps> = ({
  label,
  error,
  hint,
  showStrength = false,
  id,
  value,
  ...rest
}) => {
  const [visible, setVisible] = useState(false);
  const fieldId = id ?? label.toLowerCase().replace(/\s+/g, '-');
  const strength = showStrength ? getStrength(String(value ?? '')) : null;

  const segClass = (index: number) => {
    if (!strength || strength.level <= index) return 'strength-seg';
    if (strength.level === 1) return 'strength-seg filled-weak';
    if (strength.level === 2) return 'strength-seg filled-mid';
    return 'strength-seg filled-strong';
  };

  return (
    <div className="field">
      <label className="field-label" htmlFor={fieldId}>
        {label}
      </label>
      <div className="field-input-wrap">
        <span className="field-icon-left">
          <Icon name="lock" />
        </span>
        <input
          id={fieldId}
          type={visible ? 'text' : 'password'}
          className="field-input has-icon-left has-icon-right"
          value={value}
          {...rest}
        />
        <button
          type="button"
          className="field-icon-right"
          onClick={() => setVisible((v) => !v)}
          aria-label={visible ? 'Hide password' : 'Show password'}
        >
          <Icon name={visible ? 'eye-off' : 'eye'} />
        </button>
      </div>

      {showStrength && strength && strength.level > 0 && (
        <>
          <div className="strength-track">
            <div className={segClass(0)} />
            <div className={segClass(1)} />
            <div className={segClass(2)} />
          </div>
          <div className="strength-label">{strength.label}</div>
        </>
      )}

      {error && <div className="field-error">{error}</div>}
      {!error && hint && <div className="field-hint">{hint}</div>}
    </div>
  );
};

export default PasswordField;
