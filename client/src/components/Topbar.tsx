import type { FC } from 'react';
import Icon from './Icon';

interface TopbarProps {
  onMenuClick: () => void;
}

const Topbar: FC<TopbarProps> = ({ onMenuClick }) => {
  return (
    <div className="topbar">
      <button className="hamburger" id="hamburger" onClick={onMenuClick}>
        <Icon name="menu" strokeWidth={2} />
      </button>
      <div className="greeting">
        <div className="greeting-eyebrow">MON, 20 JUL</div>
        <div className="greeting-title">Good afternoon, Joy</div>
      </div>
      <div className="search-box">
        <Icon name="search" strokeWidth={2} />
        <input type="text" placeholder="Search transactions, cards..." />
      </div>
      <button className="icon-btn">
        <Icon name="bell" />
        <span className="dot" />
      </button>
      <div className="topbar-avatar">JA</div>
    </div>
  );
};

export default Topbar;
