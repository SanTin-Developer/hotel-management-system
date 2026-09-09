import { useEffect, useState } from "react";
import { Link, Navigate, useLocation, useNavigate } from "react-router-dom";
import { LoginForm } from "@/components/auth/LoginForm";
import { ContactModal } from "@/components/common/ContactModal";
import { useAuthStore } from "@/store/authStore";
import { useI18n } from "@/i18n";
import { ROUTES } from "@/constants/routes";

export function LoginPage() {
  const navigate = useNavigate();
  const location = useLocation();
  const token = useAuthStore((state) => state.token);
  const { t } = useI18n();
  const [contactOpen, setContactOpen] = useState(false);

  const goTo = location.state?.from ?? ROUTES.profile;

  useEffect(() => {
    if (token) {
      navigate(goTo, { replace: true });
    }
  }, [token, goTo, navigate]);

  if (token) return <Navigate to={goTo} replace />;

  return (
    <>
      <h1 className="text-2xl font-semibold tracking-tight">
        {t("auth.loginTitle")}
      </h1>
      <p className="mt-1.5 text-sm text-muted-foreground">
        {t("auth.loginSubtitle")}
      </p>
      <div className="mt-6">
        <LoginForm onSuccess={() => navigate(goTo)} />
      </div>
      <p className="mt-6 text-center text-sm text-muted-foreground">
        {t("auth.noAccount")}{" "}
        <Link
          to={ROUTES.register}
          className="font-semibold text-brand-800 hover:underline"
        >
          {t("auth.createFree")}
        </Link>
      </p>
      <p className="mt-3 text-center text-sm text-muted-foreground">
        {t("auth.needHelp")}{" "}
        <button
          type="button"
          onClick={() => setContactOpen(true)}
          className="font-semibold text-brand-800 hover:underline"
        >
          {t("auth.contactUs")}
        </button>
      </p>
      <ContactModal open={contactOpen} onOpenChange={setContactOpen} />
    </>
  );
}
