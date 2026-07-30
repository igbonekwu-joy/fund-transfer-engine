import { createContext, useCallback, useContext, useEffect, useState } from "react";
import type { ReactNode } from "react";
import type { CurrentUser } from "@/integrations/types";
import { connect } from "@/integrations/client";

interface AuthContextType {
    user: CurrentUser | null;
    loading:boolean;
}

const AuthContext = createContext<AuthContextType | null>(null);

const PUBLIC_ROUTES = ['/register'];

function isPublicRoute(): boolean {
    if (typeof window === 'undefined') return false;
    return PUBLIC_ROUTES.includes(window.location.pathname);
}

export function AuthProvider({ children }: { children: ReactNode }) {
    const [user, setUser] = useState<CurrentUser | null>(null);
    const [loading, setLoading] = useState(true);

    const refreshUser = useCallback(async () => {
        try {
            const data = await connect.getCurrentUser();
            setUser(data.user);
        } catch {
            setUser(null);
        } finally {
            setLoading(false);
        }
    }, []);

    useEffect(() => {
        if (isPublicRoute()) return;
        refreshUser();
    }, [refreshUser]);
    return (
        <AuthContext.Provider value={{ user, loading }}>
            {children}
        </AuthContext.Provider>
    );
}

export function useAuth() {
    const context = useContext(AuthContext);
    if (context === null) {
        throw new Error("useAuth must be used within a AuthProvider");
    }
    return context;
}
