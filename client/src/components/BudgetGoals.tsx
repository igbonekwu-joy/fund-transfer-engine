import type { FC } from 'react';
import { budgetGoals } from '@/data/mockData';

const formatShort = (n: number) => `₦${Math.round(n / 1000)}k`;

const BudgetGoals: FC = () => {
  return (
    <div className="card">
      <div className="card-head">
        <div className="card-title">Budget goals</div>
        <a className="card-link" href="#">
          Manage
        </a>
      </div>
      {budgetGoals.map((g) => {
        const pct = Math.min(100, Math.round((g.saved / g.target) * 100));
        return (
          <div className="goal-row" key={g.id}>
            <div className="goal-top">
              <div className="goal-name">{g.name}</div>
              <div className="goal-figs">
                <b className="num">{formatShort(g.saved)}</b> / {formatShort(g.target)}
              </div>
            </div>
            <div className="goal-track">
              <div
                className={`goal-fill${g.variant === 'amber' ? ' amber' : ''}`}
                style={{ width: `${pct}%` }}
              />
            </div>
          </div>
        );
      })}
    </div>
  );
};

export default BudgetGoals;
