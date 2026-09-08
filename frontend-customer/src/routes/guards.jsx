import { Navigate, useLocation } from "react-router-dom";
import { ROUTES } from "@/constants/routes";
import { useAuthStore } from "@/store/authStore";

export function ProtectedRoute({ children }) {
  const token = useAuthStore((state) => state.token);
  const location = useLocation();

  if (!token) {
    return (
      <Navigate
        to={ROUTES.login}
        state={{ from: `${location.pathname}${location.search}` }}
        replace
      />
    );
  }
  return children;
}

export function GuestRoute({ children }) {
  const token = useAuthStore((state) => state.token);
  if (token) {
    return <Navigate to={ROUTES.profile} replace />;
  }
  return children;
}