import { useState } from "react";
import { Link } from "react-router-dom";
import { Loader2 } from "lucide-react";
import { Button } from "@/components/ui/button";
import { Input } from "@/components/ui/input";
import { Label } from "@/components/ui/label";
import { PasswordInput } from "@/components/ui/password-input";
import { authService } from "@/services/auth/authService";
import { resetPasswordSchema } from "@/schemas/auth.schema";
import { firstError, extractApiMessage } from "@/utils/validation";
import { useI18n } from "@/i18n";
import { ROUTES } from "@/constants/routes";

export function ResetPasswordPage() {
  const { t } = useI18n();
  const [values, setValues] = useState({
    email: "",
    otp: "",
    password: "",
    password_confirmation: "",
  });
  const [errors, setErrors] = useState({});
  const [formError, setFormError] = useState(null);
  const [success, setSuccess] = useState(false);
  const [loading, setLoading] = useState(false);

  const fieldError = (field) => errors[field];

  const handleChange =
    (field) =>
    ({ target }) => {
      setValues((current) => ({ ...current, [field]: target.value }));
      setErrors((current) => ({ ...current, [field]: undefined }));
      setFormError(null);
    };

  const handleSubmit = async (event) => {
    event.preventDefault();
    const parsed = resetPasswordSchema.safeParse(values);
    if (!parsed.success) {
      const next = {};
      parsed.error.issues.forEach((issue) => {
        if (!next[issue.path[0]]) next[issue.path[0]] = issue.message;
      });
      setErrors(next);
      return;
    }
    setLoading(true);
    setFormError(null);
    try {
      await authService.resetPassword(parsed.data);
      setSuccess(true);
    } catch (err) {
      setFormError(firstError(err) ?? extractApiMessage(err));
    } finally {
      setLoading(false);
    }
  };

  return (
    <>
      <h1 className="text-2xl font-semibold tracking-tight">
        {t("auth.resetTitle")}
      </h1>
      <p className="mt-1.5 text-sm text-muted-foreground">
        {t("auth.resetSubtitle")}
      </p>
      {success ? (
        <div className="mt-6 rounded-2xl border border-brand-200 bg-brand-50 p-6 text-center">
          <h2 className="font-semibold text-brand-900">{t("auth.allSet")}</h2>
          <p className="mt-1 text-sm text-brand-800">
            {t("auth.passwordUpdated")}
          </p>
          <Link to={ROUTES.login}>
            <Button className="mt-5 w-full" variant="dark" size="pill">
              {t("auth.signInBtn")}
            </Button>
          </Link>
        </div>
      ) : (
        <form onSubmit={handleSubmit} className="mt-6 space-y-4">
          {formError && (
            <div className="rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">
              {formError}
            </div>
          )}
          <div className="space-y-1.5">
            <Label htmlFor="rp-email">{t("auth.email")}</Label>
            <Input
              id="rp-email"
              type="email"
              autoComplete="email"
              className="h-11"
              value={values.email}
              onChange={handleChange("email")}
            />
            {fieldError("email") && (
              <p className="text-xs text-red-600">{fieldError("email")}</p>
            )}
          </div>
          <div className="space-y-1.5">
            <Label htmlFor="rp-code">{t("auth.sixDigitCode")}</Label>
            <Input
              id="rp-code"
              inputMode="numeric"
              maxLength={6}
              className="h-11 text-center text-xl tracking-[0.4em]"
              value={values.otp}
              onChange={(e) => {
                setValues((current) => ({
                  ...current,
                  otp: e.target.value.replace(/\D/g, "").slice(0, 6),
                }));
              }}
            />
            {fieldError("otp") && (
              <p className="text-xs text-red-600">{fieldError("otp")}</p>
            )}
          </div>
          <div className="space-y-1.5">
            <Label htmlFor="rp-password">{t("auth.newPassword")}</Label>
            <PasswordInput
              id="rp-password"
              autoComplete="new-password"
              className="h-11"
              value={values.password}
              onChange={handleChange("password")}
            />
            {fieldError("password") && (
              <p className="text-xs text-red-600">{fieldError("password")}</p>
            )}
          </div>
          <div className="space-y-1.5">
            <Label htmlFor="rp-confirm">{t("auth.confirmPassword")}</Label>
            <PasswordInput
              id="rp-confirm"
              autoComplete="new-password"
              className="h-11"
              value={values.password_confirmation}
              onChange={handleChange("password_confirmation")}
            />
            {fieldError("password_confirmation") && (
              <p className="text-xs text-red-600">
                {fieldError("password_confirmation")}
              </p>
            )}
          </div>
          <Button
            type="submit"
            size="pill-lg"
            className="w-full"
            disabled={loading}
          >
            {loading && <Loader2 className="size-4 animate-spin" />}
            {t("auth.resetPasswordBtn")}
          </Button>
        </form>
      )}
      <p className="mt-6 text-center text-sm text-muted-foreground">
        {t("auth.needCodeAgain")}{" "}
        <Link
          to={ROUTES.forgotPassword}
          className="font-semibold text-brand-800 hover:underline"
        >
          {t("auth.requestNew")}
        </Link>
      </p>
    </>
  );
}