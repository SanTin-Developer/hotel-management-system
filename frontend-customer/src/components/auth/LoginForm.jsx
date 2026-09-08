import { useState } from "react";
import { Link } from "react-router-dom";
import { Loader2 } from "lucide-react";
import { Button } from "@/components/ui/button";
import { Input } from "@/components/ui/input";
import { Label } from "@/components/ui/label";
import { PasswordInput } from "@/components/ui/password-input";
import { useAuth } from "@/hooks/useAuth";
import { loginSchema } from "@/schemas/auth.schema";
import { firstError, extractApiMessage } from "@/utils/validation";
import { useI18n } from "@/i18n";
import { ROUTES } from "@/constants/routes";

export function LoginForm({ onSuccess, compact }) {
  const { login } = useAuth();
  const { t } = useI18n();
  const [values, setValues] = useState({ email: "", password: "" });
  const [errors, setErrors] = useState({});
  const [formError, setFormError] = useState(null);
  const [loading, setLoading] = useState(false);

  const handleChange = (field) => (event) => {
    setValues((current) => ({ ...current, [field]: event.target.value }));
    setErrors((current) => ({ ...current, [field]: undefined }));
    setFormError(null);
  };

  const handleSubmit = async (event) => {
    event.preventDefault();
    const parsed = loginSchema.safeParse(values);
    if (!parsed.success) {
      const next = {};
      parsed.error.issues.forEach((issue) => {
        next[issue.path[0]] = issue.message;
      });
      setErrors(next);
      return;
    }

    setLoading(true);
    setFormError(null);
    try {
      await login({ email: values.email, password: values.password });
      onSuccess?.();
    } catch (error) {
      setFormError(firstError(error) ?? extractApiMessage(error));
    } finally {
      setLoading(false);
    }
  };

  return (
    <form onSubmit={handleSubmit} className="space-y-4">
      <fieldset disabled={loading} className="space-y-4">
        {formError && (
          <div className="rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">
            {formError}
          </div>
        )}
        <div className="space-y-1.5">
          <Label htmlFor="login-email">{t("auth.emailOrPhone")}</Label>
          <Input
            id="login-email"
            type="text"
            autoComplete="username"
            placeholder={t("auth.identifierPlaceholder")}
            value={values.email}
            onChange={handleChange("email")}
            aria-invalid={Boolean(errors.email)}
            className="h-11"
          />
          {errors.email && (
            <p className="text-xs text-red-600">{errors.email}</p>
          )}
        </div>
        <div className="space-y-1.5">
          <div className="flex items-center justify-between">
            <Label htmlFor="login-password">{t("auth.password")}</Label>
            <Link
              to={ROUTES.forgotPassword}
              className="text-xs font-medium text-brand-800 hover:underline"
            >
              {t("auth.forgotPassword")}
            </Link>
          </div>
          <PasswordInput
            id="login-password"
            autoComplete="current-password"
            value={values.password}
            onChange={handleChange("password")}
            aria-invalid={Boolean(errors.password)}
            className="h-11"
          />
          {errors.password && (
            <p className="text-xs text-red-600">{errors.password}</p>
          )}
        </div>
        <Button
          type="submit"
          size={compact ? "pill" : "pill-lg"}
          className="w-full"
          disabled={loading}
        >
          {loading && <Loader2 className="size-4 animate-spin" />}
          {t("auth.signIn")}
        </Button>
      </fieldset>
    </form>
  );
}