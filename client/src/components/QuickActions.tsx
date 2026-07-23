import type { FC } from 'react';
import Icon, { type IconName } from '@/components/Icon';

const actions: { label: string; icon: IconName }[] = [
  { label: 'Send', icon: 'send' },
  { label: 'Top up', icon: 'plus' },
  { label: 'Cards', icon: 'card' },
  { label: 'Stats', icon: 'stats' },
];

const QuickActions: FC = () => {
  return (
    <div className="card">
      <div className="card-head" style={{ marginBottom: 14 }}>
        <div className="card-title">Quick actions</div>
      </div>
      <div className="quick-actions">
        {actions.map((a) => (
          <button className="qa-btn" key={a.label}>
            <div className="qa-icon">
              <Icon name={a.icon} strokeWidth={2} />
            </div>
            <div className="qa-label">{a.label}</div>
          </button>
        ))}
      </div>
    </div>
  );
};

export default QuickActions;
