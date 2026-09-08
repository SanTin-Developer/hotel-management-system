import { useState } from "react";
import { Link } from "react-router-dom";
import { Loader2, MailCheck } from "lucide-react";
import { Button } from "@/components/ui/button";
import { Input } from "@/components/ui/input";
import { Label } from "@/components/ui/label";
import { authService } from "@/services/auth/authService";
import { forgotPasswordSchema } from "@/schemas/auth.schema";
import { firstError, extractApiMessage } from "@/utils/validation";
import { useI18n } from "@/i18n";
import { ROUTES } from "@/constants/routes";

export function ForgotPasswordPage() {
  const { t } = useI18n();
  const [email, setEmail] = useState("");
  const [error, setError] = useState(null);
  const [sent, setSent] = useState(false);
  const [loading, setLoading] = useState(false);

  const handleSubmit = async (event) => {
    event.preventDefault();
    const parsed = forgotPasswordSchema.safeParse({ email });
    if (!parsed.success) {
      setError(parsed.error.issues[0].message);
      return;
    }
    setLoading(true);
    setError(null);
    try {
      await authService.forgotPassword({ email: email.trim() });
      setSent(true);
    } catch (err) {
      setError(firstError(err) ?? extractApiMessage(err));
    } finally {
      setLoading(false);
    }
  };

  return (
    <>
      <h1 className="text-2xl font-semibold tracking-tight">
        {t("auth.forgotTitle")}
      </h1>
      <p className="mt-1.5 text-sm text-muted-foreground">
        {t("auth.forgotSubtitle")}
      </p>
      {sent ? (
        <div className="mt-6 rounded-2xl border border-brand-200 bg-brand-50 p-6 text-center">
          <span className="mx-auto grid size-12 place-items-center rounded-full bg-brand-900 text-white">
            <MailCheck className="size-6" />
          </span>
          <h2 className="mt-4 font-semibold text-brand-900">
            {t("auth.checkEmail")}
          </h2>
          <p className="mt-1 text-sm text-brand-800">
            {t("auth.sentResetCodePre")} <strong>{email}</strong>
            {t("auth.sentResetCodePost")}
          </p>
          <Link to={ROUTES.resetPassword}>
            <Button className="mt-5 w-full" variant="dark" size="pill">
              {t("auth.haveCode")}
            </Button>
          </Link>
        </div>
      ) : (
        <form onSubmit={handleSubmit} className="mt-6 space-y-4">
          {error && (
            <div className="rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">
              {error}
            </div>
          )}
          <div className="space-y-1.5">
            <Label htmlFor="fp-email">{t("auth.email")}</Label>
            <Input
              id="fp-email"
              type="email"
              autoComplete="email"
              className="h-11"
              value={email}
              onChange={(e) => {
                setEmail(e.target.value);
                setError(null);
              }}
            />
          </div>
          <Button
            type="submit"
            size="pill-lg"
            className="w-full"
            disabled={loading}
          >
            {loading && <Loader2 className="size-4 animate-spin" />}
            {t("auth.sendResetCode")}
          </Button>
        </form>
      )}
      <p className="mt-6 text-center text-sm text-muted-foreground">
        {t("auth.rememberedIt")}{" "}
        <Link
          to={ROUTES.login}
          className="font-semibold text-brand-800 hover:underline"
        >
          {t("auth.backToSignIn")}
        </Link>
      </p>
    </>
  );
}