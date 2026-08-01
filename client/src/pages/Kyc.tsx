import { useEffect, useMemo, useState } from 'react';
import { useNavigate } from 'react-router-dom';
import Icon from '@/components/Icon';
import Sidebar from '@/components/Sidebar';
import Topbar from '@/components/Topbar';
import { handleAsync } from '@/lib/handleAsync';
import { connect } from '@/integrations/client';
import type { KycStatus } from '@/integrations/types';

const initialTier1 = {
  provider: 'nin',
  idNumber: '',
};

const initialTier2 = {
  supportingDocumentType: 'passport',
  supportingDocumentValue: '',
};

const Kyc = () => {
  const [sidebarOpen, setSidebarOpen] = useState(false);
  const [activeNav, setActiveNav] = useState('tier1');
  const [kycStatus, setKycStatus] = useState<KycStatus | null>(null);
  const [loading, setLoading] = useState(true);
  const [submitting, setSubmitting] = useState(false);
  const [tier1Form, setTier1Form] = useState(initialTier1);
  const [tier2Form, setTier2Form] = useState(initialTier2);
  const [tier3Consent, setTier3Consent] = useState(false);

  const navigate = useNavigate();

  const tier1Complete = useMemo(
    () => Boolean(kycStatus?.bvn_verified || kycStatus?.nin_verified),
    [kycStatus]
  );

  const tier2Complete = useMemo(
    () => Boolean(kycStatus?.tier === 'tier2' || kycStatus?.tier === 'tier3'),
    [kycStatus]
  );

  const tier3Complete = useMemo(
    () => Boolean(kycStatus?.tier === 'tier3'),
    [kycStatus]
  );

  const tier2Enabled = tier1Complete;
  const tier3Enabled = tier2Complete;

  const loadStatus = async () => {
    setLoading(true);
    try {
      const response = await connect.getKycStatus();
      setKycStatus(response.kyc);
    } catch {
      setKycStatus(null);
    } finally {
      setLoading(false);
    }
  };

  useEffect(() => {
    void loadStatus();
  }, []);

  const handleSubmitTier1 = async (event: React.FormEvent) => {
    event.preventDefault();
    setSubmitting(true);

    await handleAsync(
      () => connect.submitKyc({
        tier: 'tier1',
        provider: tier1Form.provider,
        id_number: tier1Form.idNumber,
      }),
      {
        successMessage: 'Tier 1 submitted successfully.',
        errorMessage: 'Unable to submit Tier 1 verification.',
        onSuccess: () => void loadStatus(),
      }
    );

    setSubmitting(false);
  };

  const handleSubmitTier2 = async (event: React.FormEvent) => {
    event.preventDefault();
    setSubmitting(true);

    await handleAsync(
      () => connect.submitKyc({
        tier: 'tier2',
        supporting_document_type: tier2Form.supportingDocumentType,
        supporting_document_value: tier2Form.supportingDocumentValue,
      }),
      {
        successMessage: 'Tier 2 submitted successfully.',
        errorMessage: 'Unable to submit Tier 2 verification.',
        onSuccess: () => void loadStatus(),
      }
    );

    setSubmitting(false);
  };

  const handleSubmitTier3 = async (event: React.FormEvent) => {
    event.preventDefault();
    setSubmitting(true);

    await handleAsync(
      () => connect.submitKyc({
        tier: 'tier3',
        tier3_consent: tier3Consent,
      }),
      {
        successMessage: 'Tier 3 submitted successfully.',
        errorMessage: 'Unable to submit Tier 3 verification.',
        onSuccess: () => void loadStatus(),
      }
    );

    setSubmitting(false);
  };

  return (
    <div className="app">
      <Sidebar isOpen={sidebarOpen} activeNav={activeNav} onNavClick={setActiveNav} />

      <div
        className={`overlay${sidebarOpen ? ' show' : ''}`}
        onClick={() => setSidebarOpen(false)}
      />

      <main className="main">
        <Topbar onMenuClick={() => setSidebarOpen(true)} />

        <div className="topbar" style={{ marginBottom: 22 }}>
          <div className="greeting">
            <p className="greeting-eyebrow">Kori · KYC Verification</p>
            <h1 className="greeting-title">Know Your Customer</h1>
          </div>
        </div>

        <section className="content-grid">
          <div className="content-left">
            <div className="card">
              <div className="card-head">
                <span className="card-title">Verification progress</span>
              </div>
              <div className="kyc-summary-grid" style={{ display: 'grid', gap: '14px' }}>
                {[
                  { label: 'Tier 1', complete: tier1Complete, enabled: true, note: 'Identity verification using NIN or BVN.' },
                  { label: 'Tier 2', complete: tier2Complete, enabled: tier2Enabled, note: 'Supporting documents verification.' },
                  { label: 'Tier 3', complete: tier3Complete, enabled: tier3Enabled, note: 'Final consent and completion.' },
                ].map((item) => (
                  <div
                    key={item.label}
                    className="card"
                    style={{
                      background: item.complete ? 'rgba(63,191,143,0.08)' : 'var(--surface)',
                      borderColor: item.enabled ? 'var(--border-soft)' : 'rgba(139,148,163,0.14)',
                      opacity: item.enabled ? 1 : 0.78,
                      padding: '18px',
                    }}
                  >
                    <div style={{ display: 'flex', alignItems: 'center', gap: 10, marginBottom: 10 }}>
                      <Icon name={item.label === 'Tier 1' ? 'shield' : item.label === 'Tier 2' ? 'card' : 'stats'} />
                      <div>
                        <div style={{ fontWeight: 600, fontSize: 15 }}>{item.label}</div>
                        <div style={{ color: 'var(--text-secondary)', fontSize: 12 }}>{item.note}</div>
                      </div>
                    </div>
                    <div style={{ display: 'flex', alignItems: 'center', justifyContent: 'space-between' }}>
                      <span className="nav-badge" style={{ margin: 0, background: item.complete ? 'rgba(63,191,143,0.18)' : 'var(--surface-raised)' }}>
                        {item.complete ? 'Completed' : item.enabled ? 'Available' : 'Locked'}
                      </span>
                      {!item.enabled && <Icon name="lock" width={16} height={16} />}
                    </div>
                  </div>
                ))}
              </div>
            </div>

            <div className="card">
              <div className="card-head">
                <span className="card-title">Tier 1 verification</span>
              </div>
              <div style={{ color: 'var(--text-secondary)', marginBottom: 18 }}>
                Verify either your NIN or BVN. Only one Tier 1 identifier may be approved.
              </div>

              <form onSubmit={handleSubmitTier1}>
                <div className="field">
                  <label className="field-label" htmlFor="provider">Provider</label>
                  <select
                    id="provider"
                    className="field-input"
                    value={tier1Form.provider}
                    onChange={(event) => setTier1Form((form) => ({ ...form, provider: event.target.value }))}
                    disabled={tier1Complete}
                  >
                    <option value="nin">NIN</option>
                    <option value="bvn">BVN</option>
                  </select>
                </div>

                <div className="field">
                  <label className="field-label" htmlFor="idNumber">ID number</label>
                  <input
                    id="idNumber"
                    className="field-input"
                    type="text"
                    placeholder="Enter your NIN or BVN"
                    value={tier1Form.idNumber}
                    onChange={(event) => setTier1Form((form) => ({ ...form, idNumber: event.target.value }))}
                    disabled={tier1Complete}
                  />
                </div>

                <button type="submit" className="save-btn" disabled={submitting || tier1Complete}>
                  {tier1Complete ? 'Tier 1 complete' : submitting ? 'Submitting…' : 'Submit Tier 1'}
                </button>
              </form>
            </div>
          </div>

          <div className="content-right">
            <div className="card">
              <div className="card-head">
                <span className="card-title">Tier 2 verification</span>
              </div>
              <div style={{ color: 'var(--text-secondary)', marginBottom: 18 }}>
                Upload supporting documentation once Tier 1 is approved.
              </div>

              <form onSubmit={handleSubmitTier2}>
                <div className="field">
                  <label className="field-label" htmlFor="documentType">Document type</label>
                  <select
                    id="documentType"
                    className="field-input"
                    value={tier2Form.supportingDocumentType}
                    onChange={(event) => setTier2Form((form) => ({ ...form, supportingDocumentType: event.target.value }))}
                    disabled={!tier2Enabled || tier2Complete}
                  >
                    <option value="passport">Passport</option>
                    <option value="utility_bill">Utility bill</option>
                    <option value="bank_statement">Bank statement</option>
                  </select>
                </div>

                <div className="field">
                  <label className="field-label" htmlFor="documentValue">Document number / account</label>
                  <input
                    id="documentValue"
                    className="field-input"
                    type="text"
                    placeholder="Enter the document details"
                    value={tier2Form.supportingDocumentValue}
                    onChange={(event) => setTier2Form((form) => ({ ...form, supportingDocumentValue: event.target.value }))}
                    disabled={!tier2Enabled || tier2Complete}
                  />
                </div>

                <button type="submit" className="save-btn" disabled={!tier2Enabled || tier2Complete || submitting}>
                  {tier2Complete ? 'Tier 2 complete' : submitting ? 'Submitting…' : 'Submit Tier 2'}
                </button>
              </form>
            </div>

            <div className="card">
              <div className="card-head">
                <span className="card-title">Tier 3 verification</span>
              </div>
              <div style={{ color: 'var(--text-secondary)', marginBottom: 18 }}>
                Finalize verification by confirming your consent after Tier 2 approval.
              </div>

              <form onSubmit={handleSubmitTier3}>
                <label className="checkbox-row" style={{ display: 'flex', alignItems: 'center', gap: 10, marginBottom: 16 }}>
                  <input
                    type="checkbox"
                    checked={tier3Consent}
                    onChange={(event) => setTier3Consent(event.target.checked)}
                    disabled={!tier3Enabled || tier3Complete}
                  />
                  <span style={{ color: 'var(--text-primary)', fontSize: 14 }}>
                    I consent to final KYC verification for tier 3.
                  </span>
                </label>

                <button type="submit" className="save-btn" disabled={!tier3Enabled || tier3Complete || submitting || !tier3Consent}>
                  {tier3Complete ? 'Tier 3 complete' : submitting ? 'Submitting…' : 'Submit Tier 3'}
                </button>
              </form>
            </div>
          </div>
        </section>
      </main>
    </div>
  );
};

export default Kyc;
