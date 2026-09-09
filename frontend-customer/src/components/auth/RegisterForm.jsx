import { useEffect, useState } from "react";
import { Loader2 } from "lucide-react";
import { Button } from "@/components/ui/button";
import { Input } from "@/components/ui/input";
import { Label } from "@/components/ui/label";
import { PasswordInput } from "@/components/ui/password-input";
import {
  Select,
  SelectContent,
  SelectItem,
  SelectTrigger,
  SelectValue,
} from "@/components/ui/select";
import { authService } from "@/services/auth/authService";
import { useAuth } from "@/hooks/useAuth";
import {
  registerSchema,
  verifyOtpSchema,
} from "@/schemas/auth.schema";
import { firstError, extractApiMessage } from "@/utils/validation";
import { useI18n } from "@/i18n";
import { COUNTRIES } from "@/constants/routes";

function fieldError(errors, field) {
  return errors[field];
}

export function RegisterForm({ onSuccess }) {
  const { setAuth } = useAuth();
  const { t } = useI18n();
  const [verificationId, setVerificationId] = useState(null);
  const [step, setStep] = useState("form");
  const [resendDisabled, setResendDisabled] = useState(0);

  const [values, setValues] = useState({
    full_name: "",
    country: "",
    nationality: "",
    date_of_birth: "",
    address: "",
    id_type: "national_id",
    id_number: "",
    email: "",
    phone: "",
    password: "",
    password_confirmation: "",
  });
  const [errors, setErrors] = useState({});
  const [formError, setFormError] = useState(null);
  const [loading, setLoading] = useState(false);

  const [otp, setOtp] = useState("");
  const [otpError, setOtpError] = useState(null);
  const [otpNote, setOtpNote] = useState(null);
  const [otpLoading, setOtpLoading] = useState(false);

  const handleChange = (field) => (event) => {
    setValues((current) => ({ ...current, [field]: event.target.value }));
    setErrors((current) => ({ ...current, [field]: undefined }));
    setFormError(null);
  };

  const handleSubmit = async (event) => {
    event.preventDefault();
    const parsed = registerSchema.safeParse(values);
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
      const res = await authService.register(parsed.data);
      setVerificationId(res.verification_id);
      setStep("otp");
      setResendDisabled(60);
    } catch (error) {
      setFormError(firstError(error) ?? extractApiMessage(error));
    } finally {
      setLoading(false);
    }
  };

  const handleVerify = async (event) => {
    event.preventDefault();
    const parsed = verifyOtpSchema.safeParse({ otp });
    if (!parsed.success) {
      setOtpError(parsed.error.issues[0].message);
      return;
    }

    setOtpLoading(true);
    setOtpError(null);
    try {
      const res = await authService.verifyOtp({
        verification_id: verificationId,
        otp,
      });
      setAuth(res);
      onSuccess?.();
    } catch (error) {
      setOtpError(firstError(error) ?? extractApiMessage(error));
    } finally {
      setOtpLoading(false);
    }
  };

  const handleResend = async () => {
    setOtpLoading(true);
    setOtpError(null);
    setOtpNote(null);
    try {
      await authService.resendOtp({ verification_id: verificationId });
      setOtpNote(t("auth.resendSent"));
      setResendDisabled(60);
    } catch (error) {
      setOtpError(firstError(error) ?? extractApiMessage(error));
    } finally {
      setOtpLoading(false);
    }
  };

  useEffect(() => {
    if (resendDisabled <= 0) return;
    const id = setInterval(() => {
      setResendDisabled((v) => (v > 0 ? v - 1 : 0));
    }, 1000);
    return () => clearInterval(id);
  }, [resendDisabled]);

  if (step === "otp") {
    return (
      <form onSubmit={handleVerify} className="space-y-4">
        <div className="rounded-xl border border-brand-200 bg-brand-50 px-4 py-3 text-sm text-brand-900">
          {t("auth.otpBannerPre")}{" "}
          <span className="font-semibold">{values.email}</span>
          {t("auth.otpBannerPost")}
        </div>

        <div className="space-y-1.5">
          <Label htmlFor="otp-code">{t("auth.verificationCode")}</Label>
          <Input
            id="otp-code"
            inputMode="numeric"
            maxLength={6}
            placeholder="••••••"
            className="h-11 text-center text-2xl tracking-[0.5em]"
            value={otp}
            onChange={(e) => {
              setOtp(e.target.value.replace(/\D/g, "").slice(0, 6));
              setOtpError(null);
            }}
            aria-invalid={Boolean(otpError)}
          />
          {otpError ? (
            <p className="text-xs text-red-600">{otpError}</p>
          ) : otpNote ? (
            <p className="text-xs text-brand-700">{otpNote}</p>
          ) : null}
        </div>

        <Button
          type="submit"
          size="pill-lg"
          className="w-full"
          disabled={otpLoading || otp.length !== 6}
        >
          {otpLoading && <Loader2 className="size-4 animate-spin" />}
          {t("auth.verifyAndCreate")}
        </Button>

        <p className="text-center text-sm text-muted-foreground">
          {t("auth.didNotReceive")}{" "}
          <button
            type="button"
            onClick={handleResend}
            disabled={resendDisabled > 0}
            className="font-semibold text-brand-800 hover:underline disabled:cursor-not-allowed disabled:opacity-50"
          >
            {resendDisabled > 0
              ? `${t("auth.resendIn")} ${resendDisabled}s`
              : t("auth.resendCode")}
          </button>
        </p>
      </form>
    );
  }

  return (
    <form onSubmit={handleSubmit} className="space-y-4">
      {formError && (
        <div className="rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">
          {formError}
        </div>
      )}

      <div className="grid gap-4 sm:grid-cols-2">
        <div className="space-y-1.5">
          <Label htmlFor="reg-name">{t("auth.fullName")}</Label>
          <Input
            id="reg-name"
            value={values.full_name}
            onChange={handleChange("full_name")}
            aria-invalid={Boolean(fieldError(errors, "full_name"))}
            className="h-11"
          />
          {fieldError(errors, "full_name") && (
            <p className="text-xs text-red-600">{fieldError(errors, "full_name")}</p>
          )}
        </div>
        <div className="space-y-1.5">
          <Label htmlFor="reg-country">{t("auth.country")}</Label>
          <Select
            value={values.country}
            onValueChange={(v) => {
              setValues((current) => ({ ...current, country: v }));
              setErrors((current) => ({ ...current, country: undefined }));
            }}
          >
            <SelectTrigger id="reg-country" className="h-11">
              <SelectValue placeholder={t("auth.selectCountry")} />
            </SelectTrigger>
            <SelectContent>
              {COUNTRIES.map((country) => (
                <SelectItem key={country} value={country}>
                  {country}
                </SelectItem>
              ))}
            </SelectContent>
          </Select>
          {fieldError(errors, "country") && (
            <p className="text-xs text-red-600">{fieldError(errors, "country")}</p>
          )}
        </div>
      </div>

      <div className="grid gap-4 sm:grid-cols-2">
        <div className="space-y-1.5">
          <Label htmlFor="reg-nationality">{t("auth.nationality")}</Label>
          <Input
            id="reg-nationality"
            value={values.nationality}
            onChange={handleChange("nationality")}
            aria-invalid={Boolean(fieldError(errors, "nationality"))}
            className="h-11"
          />
          {fieldError(errors, "nationality") && (
            <p className="text-xs text-red-600">{fieldError(errors, "nationality")}</p>
          )}
        </div>
        <div className="space-y-1.5">
          <Label htmlFor="reg-dob">{t("auth.dob")}</Label>
          <Input
            id="reg-dob"
            type="date"
            max={new Date().toISOString().split("T")[0]}
            value={values.date_of_birth}
            onChange={handleChange("date_of_birth")}
            aria-invalid={Boolean(fieldError(errors, "date_of_birth"))}
            className="h-11"
          />
          {fieldError(errors, "date_of_birth") && (
            <p className="text-xs text-red-600">{fieldError(errors, "date_of_birth")}</p>
          )}
        </div>
      </div>

      <div className="space-y-1.5">
        <Label htmlFor="reg-address">{t("auth.address")}</Label>
        <Input
          id="reg-address"
          value={values.address}
          onChange={handleChange("address")}
          aria-invalid={Boolean(fieldError(errors, "address"))}
          className="h-11"
        />
        {fieldError(errors, "address") && (
          <p className="text-xs text-red-600">{fieldError(errors, "address")}</p>
        )}
      </div>

      <div className="grid gap-4 sm:grid-cols-2">
        <div className="space-y-1.5">
          <Label htmlFor="reg-id-type">{t("auth.idType")}</Label>
          <Select
            value={values.id_type}
            onValueChange={(v) => {
              setValues((current) => ({ ...current, id_type: v }));
              setErrors((current) => ({ ...current, id_type: undefined }));
            }}
          >
            <SelectTrigger id="reg-id-type" className="h-11">
              <SelectValue />
            </SelectTrigger>
            <SelectContent>
              <SelectItem value="national_id">{t("auth.nationalId")}</SelectItem>
              <SelectItem value="passport">{t("auth.passport")}</SelectItem>
            </SelectContent>
          </Select>
        </div>
        <div className="space-y-1.5">
          <Label htmlFor="reg-id-number">{t("auth.idNumber")}</Label>
          <Input
            id="reg-id-number"
            value={values.id_number}
            onChange={handleChange("id_number")}
            aria-invalid={Boolean(fieldError(errors, "id_number"))}
            className="h-11"
          />
          {fieldError(errors, "id_number") && (
            <p className="text-xs text-red-600">{fieldError(errors, "id_number")}</p>
          )}
        </div>
      </div>

      <div className="grid gap-4 sm:grid-cols-2">
        <div className="space-y-1.5">
          <Label htmlFor="reg-email">{t("auth.email")}</Label>
          <Input
            id="reg-email"
            type="email"
            autoComplete="email"
            value={values.email}
            onChange={handleChange("email")}
            aria-invalid={Boolean(fieldError(errors, "email"))}
            className="h-11"
          />
          {fieldError(errors, "email") && (
            <p className="text-xs text-red-600">{fieldError(errors, "email")}</p>
          )}
        </div>
        <div className="space-y-1.5">
          <Label htmlFor="reg-phone">{t("auth.phone")}</Label>
          <Input
            id="reg-phone"
            type="tel"
            autoComplete="tel"
            value={values.phone}
            onChange={handleChange("phone")}
            aria-invalid={Boolean(fieldError(errors, "phone"))}
            className="h-11"
          />
          {fieldError(errors, "phone") && (
            <p className="text-xs text-red-600">{fieldError(errors, "phone")}</p>
          )}
        </div>
      </div>

      <div className="grid gap-4 sm:grid-cols-2">
        <div className="space-y-1.5">
          <Label htmlFor="reg-password">{t("auth.password")}</Label>
          <PasswordInput
            id="reg-password"
            autoComplete="new-password"
            value={values.password}
            onChange={handleChange("password")}
            aria-invalid={Boolean(fieldError(errors, "password"))}
            className="h-11"
          />
          {fieldError(errors, "password") && (
            <p className="text-xs text-red-600">{fieldError(errors, "password")}</p>
          )}
        </div>
        <div className="space-y-1.5">
          <Label htmlFor="reg-password-confirm">{t("auth.confirmPassword")}</Label>
          <PasswordInput
            id="reg-password-confirm"
            autoComplete="new-password"
            value={values.password_confirmation}
            onChange={handleChange("password_confirmation")}
            aria-invalid={Boolean(fieldError(errors, "password_confirmation"))}
            className="h-11"
          />
          {fieldError(errors, "password_confirmation") && (
            <p className="text-xs text-red-600">
              {fieldError(errors, "password_confirmation")}
            </p>
          )}
        </div>
      </div>

      <Button
        type="submit"
        size="pill-lg"
        className="w-full"
        disabled={loading}
      >
        {loading && <Loader2 className="size-4 animate-spin" />}
        {t("auth.createAccount")}
      </Button>
    </form>
  );
}