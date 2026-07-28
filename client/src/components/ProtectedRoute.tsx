import { Navigate, useLocation } from "react-router-dom";
import { useEffect, useState } from "react";
import { connect } from "@/integrations/client";
import type { CurrentUser } from "@/integrations/types";

export default function ProtectedRoute({ children }: { children: React.ReactNode }) {
  const location = useLocation();
  const isLoginRoute = location.pathname === "/login";
  const [loading, setLoading] = useState(true);
  const [user, setUser] = useState<CurrentUser | null>(null);
  const isAuthenticated = Boolean(user);

  useEffect(() => {
    let cancelled = false;

    (async () => {
      try {
        const currentUser = await connect.getCurrentUser();
        console.log(currentUser)

        if (!cancelled) {
          setUser(currentUser.user);
        }
      } catch {
        if (!cancelled) {
          setUser(null);
        }
      } finally {
        if (!cancelled) {
          setLoading(false);
        }
      }
    })();

    return () => {
      cancelled = true;
    };
  }, []);

  if (loading) {
    return (
      <div className="min-h-screen flex items-center justify-center bg-background">
        <div className="animate-spin h-8 w-8 border-4 border-primary border-t-transparent rounded-full" />
      </div>
    );
  }

  if (isLoginRoute) {
    if (isAuthenticated) {
      return <Navigate to="/" replace />;
    }

    return <>{children}</>;
  }

  if (!isAuthenticated) {
    return <Navigate to="/login" replace />;
  }

  return <>{children}</>;
}
