import type { FC } from 'react';
import Icon, { type IconName } from '@/components/Icon';

interface StatCardProps {
  label: string;
  amount: string;
  deltaLabel: string;
  deltaPositive: boolean;
  iconName: IconName;
  variant: 'in' | 'out';
}

const StatCard: FC<StatCardProps> = ({ label, amount, deltaLabel, deltaPositive, iconName, variant }) => {
  return (
    <div className="card stat-card">
      <div className="stat-top">
        <div className="stat-label">{label}</div>
        <div className={`stat-icon ${variant}`}>
          <Icon name={iconName} strokeWidth={2.5} />
        </div>
      </div>
      <div className="stat-amount num">{amount}</div>
      <div className={`stat-sub${deltaPositive ? '' : ' neg'}`}>
        <b>{deltaLabel}</b> vs last month
      </div>
    </div>
  );
};

export default StatCard;
