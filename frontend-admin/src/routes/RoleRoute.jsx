import { Navigate, Outlet } from "react-router-dom";
import { useAuthStore } from "@/store/authStore";

/**
 * Guards a route by role. If the user isn't authenticated, redirects to /login.
 * If they lack any of the required roles, redirects to /dashboard.
 */
export default function RoleRoute({ roles, children }) {
  const isAuthenticated = useAuthStore((state) => state.isAuthenticated());
  const hasAnyRole = useAuthStore((state) => state.hasAnyRole);

  if (!isAuthenticated) {
    return <Navigate to="/login" replace />;
  }

  if (!hasAnyRole(roles)) {
    return <Navigate to="/dashboard" replace />;
  }

  return children ?? <Outlet />;
}