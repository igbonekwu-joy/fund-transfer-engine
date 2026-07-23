import type { FC } from 'react';
import Icon, { type IconName } from '@/components/Icon';
import { primaryNav, secondaryNav } from '@/data/mockData';

interface SidebarProps {
  isOpen: boolean;
  activeNav: string;
  onNavClick: (id: string) => void;
}

const Sidebar: FC<SidebarProps> = ({ isOpen, activeNav, onNavClick }) => {
  return (
    <aside className={`sidebar${isOpen ? ' open' : ''}`} id="sidebar">
      <div className="brand">
        <div className="brand-mark">
          <Icon name="diamond" stroke="#08251A" strokeWidth={2} />
        </div>
        <div className="brand-name">Kori</div>
      </div>

      <div className="nav-label">Menu</div>
      <nav className="nav">
        {primaryNav.map((item) => (
          <button
            key={item.id}
            className={`nav-item${activeNav === item.id ? ' active' : ''}`}
            onClick={() => onNavClick(item.id)}
          >
            <Icon name={item.icon as IconName} />
            {item.label}
            {item.badge && <span className="nav-badge">{item.badge}</span>}
          </button>
        ))}
      </nav>

      <div className="nav-label">Account</div>
      <nav className="nav">
        {secondaryNav.map((item) => (
          <button
            key={item.id}
            className={`nav-item${activeNav === item.id ? ' active' : ''}`}
            onClick={() => onNavClick(item.id)}
          >
            <Icon name={item.icon as IconName} />
            {item.label}
          </button>
        ))}
      </nav>

      <div className="sidebar-spacer" />

      <div className="sidebar-card">
        <div className="sidebar-card-title">Go paperless</div>
        <div className="sidebar-card-sub">Switch on e-statements and skip the mail.</div>
        <button className="sidebar-card-btn">Turn on</button>
      </div>

      <div className="user-chip">
        <div className="avatar">JA</div>
        <div>
          <div className="user-chip-name">Joy Adeyemi</div>
          <div className="user-chip-role">Personal account</div>
        </div>
        <button className="user-chip-more">
          <Icon name="more" width={16} height={16} strokeWidth={2} />
        </button>
      </div>
    </aside>
  );
};

export default Sidebar;
