import type { FC } from 'react';
import { bills } from '@/data/mockData';

const UpcomingBills: FC = () => {
  return (
    <div className="card">
      <div className="card-head">
        <div className="card-title">Upcoming bills</div>
      </div>
      {bills.map((b) => (
        <div className="bill-row" key={b.id}>
          <div className="bill-date">
            <span className="d">{b.day}</span>
            <span className="m">{b.month}</span>
          </div>
          <div className="bill-mid">
            <div className="bill-name">{b.name}</div>
            <div className="bill-sub">{b.method}</div>
          </div>
          <div className="bill-amount num">₦{b.amount.toLocaleString()}</div>
        </div>
      ))}
    </div>
  );
};

export default UpcomingBills;
