import { Navigate, Outlet } from "react-router-dom";
import { useAuthStore } from "@/store/authStore";

/**
 * Wraps protected pages. If the user isn't authenticated, redirect to /login.
 * Usage: wrap a layout route so all nested routes are protected at once.
 */
export default function ProtectedRoute({ children }) {
  const isAuthenticated = useAuthStore((state) => state.isAuthenticated());

  if (!isAuthenticated) {
    return <Navigate to="/login" replace />;
  }

  // If used as a layout-route wrapper, render <Outlet />;
  // if passed children directly, render those instead.
  return children ?? <Outlet />;
}
