import type { FC } from 'react';
import Icon from '@/components/Icon';
import { useAuth } from '@/contexts/AuthContext';

interface TopbarProps {
  onMenuClick: () => void;
}

const getGreeting = (): string => {
  const hour = new Date().getHours();

  if (hour >= 0 && hour < 12) {
    return 'Good morning';
  }

  if (hour >= 12 && hour < 16) {
    return 'Good afternoon';
  }

  if (hour >= 16 && hour < 20) {
    return 'Good evening';
  }

  return 'Good night';
};

const Topbar: FC<TopbarProps> = ({ onMenuClick }) => {
    const { user } = useAuth();
  return (
    <div className="topbar">
      <button className="hamburger" id="hamburger" onClick={onMenuClick}>
        <Icon name="menu" strokeWidth={2} />
      </button>
      <div className="greeting">
        <div className="greeting-eyebrow">MON, 20 JUL</div>
        <div className="greeting-title">{getGreeting()}, {user?.name}</div>
      </div>
      <div className="search-box">
        <Icon name="search" strokeWidth={2} />
        <input type="text" placeholder="Search transactions, cards..." />
      </div>
      <button className="icon-btn">
        <Icon name="bell" />
        <span className="dot" />
      </button>
      <div className="topbar-avatar">{ user?.initials }</div>
    </div>
  );
};

export default Topbar;
