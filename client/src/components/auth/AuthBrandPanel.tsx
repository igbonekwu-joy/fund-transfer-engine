import type { FC } from 'react';
import Icon from '../Icon';

const AuthBrandPanel: FC = () => {
    const appName = import.meta.env.VITE_APP_NAME;
  return (
    <div className="auth-brand">
      <div className="auth-brand-top">
        <div className="brand">
          <div className="brand-mark">
            <Icon name="diamond" stroke="#08251A" strokeWidth={2} />
          </div>
          <div className="brand-name">{appName}</div>
        </div>
      </div>

      <div className="auth-brand-mid">
        <div className="auth-brand-headline">Banking that moves at the speed of your day.</div>
        <div className="auth-brand-sub">
          Track spending, hit savings goals, and move money — all from one calm, dark-mode dashboard.
        </div>
        <div className="auth-proof-row" style={{ marginTop: 28 }}>
          <div className="auth-proof">
            <div className="auth-proof-value num">120k+</div>
            <div className="auth-proof-label">Active accounts</div>
          </div>
          <div className="auth-proof">
            <div className="auth-proof-value num">99.98%</div>
            <div className="auth-proof-label">Uptime</div>
          </div>
          <div className="auth-proof">
            <div className="auth-proof-value num">₦4.2bn</div>
            <div className="auth-proof-label">Moved monthly</div>
          </div>
        </div>
      </div>

      <div className="auth-brand-bottom">
        <div className="auth-brand-quote">
          "{appName} replaced three apps I used to juggle just to see where my money went."
          <b>— Amaka O., {appName} customer</b>
        </div>
      </div>
    </div>
  );
};

export default AuthBrandPanel;
