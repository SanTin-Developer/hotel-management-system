import { useEffect } from "react";
import { Link, Navigate, useNavigate } from "react-router-dom";
import { RegisterForm } from "@/components/auth/RegisterForm";
import { useAuthStore } from "@/store/authStore";
import { useI18n } from "@/i18n";
import { ROUTES } from "@/constants/routes";

export function RegisterPage() {
  const navigate = useNavigate();
  const token = useAuthStore((state) => state.token);
  const { t } = useI18n();

  useEffect(() => {
    if (token) navigate(ROUTES.profile, { replace: true });
  }, [token, navigate]);

  if (token) return <Navigate to={ROUTES.profile} replace />;

  return (
    <>
      <h1 className="text-2xl font-semibold tracking-tight">
        {t("auth.registerTitle")}
      </h1>
      <p className="mt-1.5 text-sm text-muted-foreground">
        {t("auth.registerSubtitle")}
      </p>
      <div className="mt-6">
        <RegisterForm onSuccess={() => navigate(ROUTES.profile)} />
      </div>
      <p className="mt-6 text-center text-sm text-muted-foreground">
        {t("auth.haveAccount")}{" "}
        <Link
          to={ROUTES.login}
          className="font-semibold text-brand-800 hover:underline"
        >
          {t("auth.signInLink")}
        </Link>
      </p>
    </>
  );
}