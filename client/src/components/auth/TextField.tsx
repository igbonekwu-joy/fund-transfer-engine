import type { FC, InputHTMLAttributes } from 'react';
import Icon, { type IconName } from '../Icon';

interface TextFieldProps extends InputHTMLAttributes<HTMLInputElement> {
  label: string;
  icon?: IconName;
  error?: string;
  hint?: string;
}

const TextField: FC<TextFieldProps> = ({ label, icon, error, hint, id, ...rest }) => {
  const fieldId = id ?? label.toLowerCase().replace(/\s+/g, '-');

  return (
    <div className="field">
      <label className="field-label" htmlFor={fieldId}>
        {label}
      </label>
      <div className="field-input-wrap">
        {icon && (
          <span className="field-icon-left">
            <Icon name={icon} />
          </span>
        )}
        <input
          id={fieldId}
          className={`field-input${icon ? ' has-icon-left' : ''}`}
          {...rest}
        />
      </div>
      {error && <div className="field-error">{error}</div>}
      {!error && hint && <div className="field-hint">{hint}</div>}
    </div>
  );
};

export default TextField;
