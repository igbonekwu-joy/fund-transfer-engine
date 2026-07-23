import { useState, type FC } from 'react';
import { spendData, todayIndex } from '@/data/mockData';

const SpendingChart: FC = () => {
  const [tab, setTab] = useState<'month' | 'week'>('week');
  const max = Math.max(...spendData.map((b) => b.value));

  return (
    <div className="card">
      <div className="card-head">
        <div className="card-title">Spending activity</div>
        <div className="chart-tabs">
          <button
            className={`chart-tab${tab === 'month' ? ' active' : ''}`}
            onClick={() => setTab('month')}
          >
            Month
          </button>
          <button
            className={`chart-tab${tab === 'week' ? ' active' : ''}`}
            onClick={() => setTab('week')}
          >
            Week
          </button>
        </div>
      </div>
      <div className="bars">
        {spendData.map((b, i) => (
          <div className="bar-col" key={b.day}>
            <div
              className={`bar${i === todayIndex ? ' today' : ''}`}
              style={{ height: `${((b.value / max) * 100).toFixed(0)}%` }}
            />
            <div className="bar-day">{b.day}</div>
          </div>
        ))}
      </div>
    </div>
  );
};

export default SpendingChart;
