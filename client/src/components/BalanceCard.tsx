import { useState, type FC } from 'react';
import Icon from './Icon';

const BalanceCard: FC = () => {
  const [hidden, setHidden] = useState(false);

  return (
    <div className="card balance-card">
      <div className="balance-top">
        <div className="balance-label">
          Total balance
          <button className="eye-btn" onClick={() => setHidden((h) => !h)}>
            <Icon name="eye" />
          </button>
        </div>
        <div className="account-pill">•••• 4471</div>
      </div>
      <div>
        <div className="balance-amount num">
          {hidden ? (
            '••••••••'
          ) : (
            <>
              ₦2,438,912<span className="cents">.40</span>
            </>
          )}
        </div>
        <div className="balance-delta">
          <Icon name="arrow-up" strokeWidth={2.5} width={12} height={12} />
          +4.2% from last month
        </div>
      </div>
      <div className="spark-wrap">
        <svg viewBox="0 0 300 60" preserveAspectRatio="none">
          <path
            d="M0,45 C20,44 30,30 50,32 C70,34 80,20 100,22 C120,24 130,10 150,14 C170,18 180,35 200,30 C220,25 230,8 250,10 C270,12 280,26 300,18"
            fill="none"
            stroke="#3FBF8F"
            strokeWidth={2}
            className="spark-path"
            strokeLinecap="round"
          />
        </svg>
      </div>
    </div>
  );
};

export default BalanceCard;
