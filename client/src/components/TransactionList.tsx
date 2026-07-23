import type { FC } from 'react';
import Icon from './Icon';
import { transactions } from '../data/mockData';

const formatAmount = (n: number) => (n < 0 ? '−' : '+') + '₦' + Math.abs(n).toLocaleString();

const TransactionList: FC = () => {
  return (
    <div className="card">
      <div className="card-head">
        <div className="card-title">Recent transactions</div>
        <a className="card-link" href="#">
          See all
          <Icon name="chevron-right" strokeWidth={2.5} />
        </a>
      </div>
      <div className="txn-list">
        {transactions.map((t) => (
          <div className="txn-row" key={t.id}>
            <div className="txn-icon">
              <Icon name={t.icon} />
            </div>
            <div className="txn-mid">
              <div className="txn-name">{t.name}</div>
              <div className="txn-meta">{t.meta}</div>
            </div>
            <div className={`txn-amount num ${t.type === 'in' ? 'pos' : 'neg'}`}>
              {formatAmount(t.amount)}
            </div>
          </div>
        ))}
      </div>
    </div>
  );
};

export default TransactionList;
