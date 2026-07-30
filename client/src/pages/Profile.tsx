import { useState, useRef, useEffect } from "react";
import {
    User,
    Phone,
    Mail,
    MapPin,
    Calendar,
    Users as GenderIcon,
    Check,
    Pencil,
    CreditCard,
} from "lucide-react";
import Sidebar from "@/components/Sidebar";
import Topbar from "@/components/Topbar";
import type { FieldDef, ProfileData } from "@/integrations/types";
import { useAuth } from "@/contexts/AuthContext";
import { handleAsync } from "@/lib/handleAsync";
import { connect } from "@/integrations/client";

const FIELD_DEFS: FieldDef[] = [
    { key: "fullName", label: "Full name", icon: User, type: "text", placeholder: "e.g. Joy Adeyemi" },
    { key: "mobile", label: "Mobile number", icon: Phone, type: "tel", placeholder: "e.g. 0803 123 4567" },
    {
        key: "gender",
        label: "Gender",
        icon: GenderIcon,
        type: "select",
        options: ["Male", "Female", "Prefer not to say"],
    },
    { key: "dob", label: "Date of birth", icon: Calendar, type: "date" },
    { key: "email", label: "Email address", icon: Mail, type: "email", placeholder: "e.g. joy@kori.app" },
    { key: "address", label: "Address", icon: MapPin, type: "textarea", placeholder: "Street, city, state" },
];

const INITIAL_PROFILE: ProfileData = {
    fullName: "",
    mobile: "",
    gender: "",
    dob: "",
    email: "",
    address: "",
};

// function generateNuban(): string {
//     // Nigerian-style 10-digit account number, first digit 2–9 (never starts with 0)
//     let n = String(Math.floor(Math.random() * 8) + 2);
//     for (let i = 0; i < 9; i++) n += Math.floor(Math.random() * 10);
//     return n;
// }

// function groupDigits(str: string): string {
//     return str.replace(/(\d{4})(?=\d)/g, "$1 ");
// }

const ProfilePage = () => {
    const [sidebarOpen, setSidebarOpen] = useState(false);
    const [profile, setProfile] = useState<ProfileData>(INITIAL_PROFILE);
    const [draft, setDraft] = useState<ProfileData>(INITIAL_PROFILE);
    const [editing, setEditing] = useState(false);
    const [activeNav, setActiveNav] = useState('settings');
    // const [accountNumber, setAccountNumber] = useState<string | null>(null);
    // const [rolling, setRolling] = useState(false);
    // const [copied, setCopied] = useState(false);
    const [savedFlash, setSavedFlash] = useState(false);
    const intervalRef = useRef<ReturnType<typeof setInterval> | null>(null);
    const { user } = useAuth();
    const handleNavClick = (id: string) => {
        setActiveNav(id);
        setSidebarOpen(false);
    };

    useEffect(() => {
        if (!user) {
            return;
        }
        INITIAL_PROFILE.fullName = user.name;
        INITIAL_PROFILE.email = user.email;
        INITIAL_PROFILE.mobile = user.phone;
        INITIAL_PROFILE.gender = user.gender;
        INITIAL_PROFILE.dob = user.dob;
        INITIAL_PROFILE.address = user.address;
    }, [user]);

    useEffect(() => () => {
        if (intervalRef.current) clearInterval(intervalRef.current);
    }, []);

    function formatLocalDate(dateStr: string): string {
        const dateOnly = dateStr.split(" ")[0];
        const [year, month, day] = dateOnly.split("-").map(Number);

        return new Date(year, month - 1, day).toLocaleDateString(undefined, {
            day: "numeric",
            month: "long",
            year: "numeric",
        });
    }

    // const handleGenerate = () => {
    //     if (rolling) return;
    //     setCopied(false);
    //     setRolling(true);
    //     let ticks = 0;
    //     intervalRef.current = setInterval(() => {
    //         setAccountNumber(generateNuban());
    //         ticks += 1;
    //         if (ticks >= 10) {
    //             if (intervalRef.current) clearInterval(intervalRef.current);
    //             setRolling(false);
    //         }
    //     }, 60);
    // };

    // const handleCopy = async () => {
    //     if (!accountNumber) return;
    //     try {
    //         await navigator.clipboard.writeText(accountNumber);
    //         setCopied(true);
    //         setTimeout(() => setCopied(false), 1600);
    //     } catch {
    //         // clipboard unavailable — fail silently
    //     }
    // };

    const startEditing = () => {
        setDraft(profile);
        setEditing(true);
    };

    const cancelEditing = () => {
        setDraft(profile);
        setEditing(false);
    };

    const saveChanges = async (e: React.FormEvent) => {
        e.preventDefault();

        await handleAsync(
            () => connect.updateProfile(draft),
            {
                successMessage: 'Success!',
                errorMessage: 'Something went terribly wrong',
                onSuccess: () => '',
            }
        );

        setProfile(draft);
        setEditing(false);
        setSavedFlash(true);
        setTimeout(() => setSavedFlash(false), 2200);
    };

    const initials = profile.fullName
        .split(" ")
        .filter(Boolean)
        .slice(0, 2)
        .map((w) => w[0]?.toUpperCase())
        .join("");

    function groupDigits(accountNumber: string): import("react").ReactNode {
        return accountNumber.replace(/(\d{4})(?=\d)/g, "$1 ");
        // throw new Error("Function not implemented.");
    }

    return (
        <div className="app">
            <Sidebar isOpen={sidebarOpen} activeNav={activeNav} onNavClick={handleNavClick} />

            <div
                className={`overlay${sidebarOpen ? " show" : ""}`}
                onClick={() => setSidebarOpen(false)}
            />

            <main className="main">
                <Topbar onMenuClick={() => setSidebarOpen(true)} />

                <div className="topbar" style={{ marginBottom: 22 }}>
                    <div className="greeting">
                        <p className="greeting-eyebrow">Kori · Account</p>
                        <h1 className="greeting-title">Profile</h1>
                    </div>
                    <div className="profile-toast" style={{ opacity: savedFlash ? 1 : 0 }}>
                        <Check /> Profile updated
                    </div>
                </div>

                <section className="profile-layout">
                    {/* Identity / account number card */}
                    <div className="card identity-card">
                        <div className="avatar">{initials || "?"}</div>
                        <p className="identity-name">{profile.fullName || "Unnamed user"}</p>
                        <p className="identity-email">{profile.email || "No email on file"}</p>

                        <div className="acct-block">
                            <p className="acct-block-label">
                                <CreditCard /> Account number
                            </p>
                            <div className="acct-number-row">
                                <span className={`acct-number${user?.account_number ? " set" : ""}`}>
                                    {user?.account_number ? groupDigits(user?.account_number) : "Complete your profile to get an account number"}
                                </span>
                                {/* {accountNumber && !rolling && (
                                    <button className="acct-copy-btn" onClick={handleCopy} aria-label="Copy account number">
                                        {copied ? <Check /> : <Copy />}
                                    </button>
                                )} */}
                            </div>
                            {/* <button
                                className={`generate-btn${rolling ? " spin" : ""}`}
                                onClick={handleGenerate}
                                disabled={rolling}
                            >
                                <RefreshCw />
                                {accountNumber ? "Regenerate account number" : "Generate account number"}
                            </button> */}
                        </div>
                    </div>

                    {/* Editable details panel */}
                    <form className="card profile-panel" onSubmit={saveChanges}>
                        <div className="card-head">
                            <span className="card-title">Personal details</span>
                            {editing ? (
                                <div className="btn-row">
                                    <button type="button" className="cancel-btn" onClick={cancelEditing}>
                                        Cancel
                                    </button>
                                    <button type="submit" className="save-btn">
                                        <Check /> Save changes
                                    </button>
                                </div>
                            ) : (
                                <button type="button" className="edit-btn" onClick={startEditing}>
                                    <Pencil /> Edit profile
                                </button>
                            )}
                        </div>

                        <div className="profile-field-grid">
                            {FIELD_DEFS.map(({ key, label, icon: Icon, type, placeholder, options }) => {
                                const isFull = type === "textarea";
                                const value = editing ? draft[key] : profile[key];
                                return (
                                    <div className={`field${isFull ? " full" : ""}`} key={key}>
                                        <label className="field-label" htmlFor={key}>
                                            <span className="field-label-icon">
                                                <Icon />
                                            </span>{" "}
                                            {label}
                                        </label>

                                        {!editing && (
                                            <div className="field-value">
                                                {type === "date" && value ? formatLocalDate(value) : value || "—"}
                                            </div>
                                        )}

                                        {editing && type === "select" && (
                                            <select
                                                id={key}
                                                className="field-input"
                                                value={value}
                                                onChange={(e) => setDraft({ ...draft, [key]: e.target.value })}
                                            >
                                                {options!.map((opt) => (
                                                    <option key={opt} value={opt}>
                                                        {opt}
                                                    </option>
                                                ))}
                                            </select>
                                        )}

                                        {editing && type === "textarea" && (
                                            <textarea
                                                id={key}
                                                className="field-input textarea"
                                                value={value}
                                                placeholder={placeholder}
                                                onChange={(e) => setDraft({ ...draft, [key]: e.target.value })}
                                            />
                                        )}

                                        {editing && type !== "select" && type !== "textarea" && (
                                            <div className="field-input-wrap">
                                                <input
                                                    id={key}
                                                    className="field-input"
                                                    type={type}
                                                    value={value}
                                                    placeholder={placeholder}
                                                    onChange={(e) => setDraft({ ...draft, [key]: e.target.value })}
                                                />
                                            </div>
                                        )}
                                    </div>
                                );
                            })}
                        </div>
                    </form>
                </section>
            </main>
        </div>
    );
};

export default ProfilePage;
