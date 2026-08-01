import { useEffect, useState } from 'react';
import type { FC } from 'react';
import Icon, { type IconName } from '@/components/Icon';
import { kycNav, primaryNav, secondaryNav } from '@/data/mockData';
import { handleAsync } from '@/lib/handleAsync';
import { connect } from '@/integrations/client';
import type { KycStatus } from '@/integrations/types';
import { useNavigate } from 'react-router-dom';

interface SidebarProps {
  isOpen: boolean;
  activeNav: string;
  onNavClick: (id: string) => void;
}

const Sidebar: FC<SidebarProps> = ({ isOpen, activeNav, onNavClick }) => {
    const appName = import.meta.env.VITE_APP_NAME;
    const navigate = useNavigate();
    const [kycStatus, setKycStatus] = useState<KycStatus | null>(null);

    const tier1Approved = Boolean(kycStatus?.bvn_verified || kycStatus?.nin_verified);
    const tier2Approved = tier1Approved && (kycStatus?.tier === 'tier2' || kycStatus?.tier === 'tier3') && kycStatus?.status === 'approved';
    const tier2Enabled = tier1Approved;
    const tier3Enabled = tier2Approved;

    useEffect(() => {
        let cancelled = false;

        (async () => {
            try {
                const result = await connect.getKycStatus();
                if (!cancelled) {
                    setKycStatus(result.kyc);
                }
            } catch {
                if (!cancelled) {
                    setKycStatus(null);
                }
            }
        })();

        return () => {
            cancelled = true;
        };
    }, []);

    const handleLogout = async () => {
        await handleAsync(
                () => connect.signOut(),
                {
                    successMessage: '',
                    errorMessage: 'Something went terribly wrong',
                    onSuccess: () => setTimeout(() => { window.location.href = '/login' }, 1000),
                }
            );

    }
    return (
        <aside className={`sidebar${isOpen ? ' open' : ''}`} id="sidebar" style={{ "overflowY": "auto"}}>
            <div className="brand">
                <div className="brand-mark">
                <Icon name="diamond" stroke="#08251A" strokeWidth={2} />
                </div>
                <div className="brand-name">{appName}</div>
            </div>

            <div className="nav-label">Menu</div>
            <nav className="nav">
                {primaryNav.map((item) => (
                <button
                    key={item.id}
                    className={`nav-item${activeNav === item.id ? ' active' : ''}`}
                    onClick={() => {
                        switch (item.id) {
                            case 'overview':
                                navigate('/');
                                break;
                        }
                        onNavClick(item.id)
                    }}

                >
                    <Icon name={item.icon as IconName} />
                    {item.label}
                    {item.badge && <span className="nav-badge">{item.badge}</span>}
                </button>
                ))}
            </nav>

            <div className="nav-label">KYC</div>
            <nav className="nav">
                {kycNav.map((item) => {
                const disabled = item.id === 'tier2'
                    ? !tier2Enabled
                    : item.id === 'tier3'
                    ? !tier3Enabled
                    : false;

                return (
                    <button
                    key={item.id}
                    className={`nav-item${activeNav === item.id ? ' active' : ''}${disabled ? ' disabled' : ''}`}
                    type="button"
                    onClick={() => {
                        if (disabled) return;
                        navigate('/kyc');
                        onNavClick(item.id);
                    }}
                    >
                    <Icon name={item.icon as IconName} />
                    {item.label}
                    {disabled && <span className="nav-badge">Locked</span>}
                    </button>
                );
                })}
            </nav>

            <div className="nav-label">Account</div>
            <nav className="nav">
                {secondaryNav.map((item) => (
                <button
                    key={item.id}
                    className={`nav-item${activeNav === item.id ? ' active' : ''}`}
                    onClick={() => {
                        switch (item.id) {
                            case 'logout':
                                handleLogout();
                                break;
                            case 'settings':
                            navigate('/profile');
                                break;
                        }
                        onNavClick(item.id)
                    }}
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
