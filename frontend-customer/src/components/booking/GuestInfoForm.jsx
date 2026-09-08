import { useState } from "react";
import { Loader2, Save } from "lucide-react";
import { Button } from "@/components/ui/button";
import { Input } from "@/components/ui/input";
import { Label } from "@/components/ui/label";
import { Textarea } from "@/components/ui/textarea";
import {
  Select,
  SelectContent,
  SelectItem,
  SelectTrigger,
  SelectValue,
} from "@/components/ui/select";
import { guestInfoSchema } from "@/schemas/booking.schema";
import { useBookingStore } from "@/store/bookingStore";
import { useAuthStore } from "@/store/authStore";
import { COUNTRIES } from "@/constants/routes";
import { useI18n } from "@/i18n";

function fieldError(errors, field) {
  return errors[field];
}

export function GuestInfoForm({ onSaved }) {
  const localGuest = useBookingStore((state) => state.guest);
  const setBookingGuest = useBookingStore((state) => state.setGuest);
  const profileGuest = useAuthStore((state) => state.guest);

  const [values, setValues] = useState(() => ({
    full_name: localGuest.full_name || profileGuest?.full_name || "",
    email: localGuest.email || profileGuest?.email || "",
    phone: localGuest.phone || profileGuest?.phone || "",
    country: localGuest.country || profileGuest?.country || "",
    id_type: localGuest.id_type || profileGuest?.id_type || "national_id",
    id_number: localGuest.id_number || profileGuest?.id_number || "",
    address: localGuest.address || "",
    special_request: localGuest.special_request || "",
  }));
  const [errors, setErrors] = useState({});
  const [saving, setSaving] = useState(false);
  const { t } = useI18n();

  const handleChange =
    (field) =>
    ({ target }) => {
      const value = target.value;
      setValues((current) => ({ ...current, [field]: value }));
      setErrors((current) => ({ ...current, [field]: undefined }));
    };

  const handleSubmit = (event) => {
    event.preventDefault();
    const parsed = guestInfoSchema.safeParse(values);
    if (!parsed.success) {
      const next = {};
      parsed.error.issues.forEach((issue) => {
        if (!next[issue.path[0]]) next[issue.path[0]] = issue.message;
      });
      setErrors(next);
      return;
    }

    setSaving(true);
    setBookingGuest(parsed.data);
    setSaving(false);
    onSaved();
  };

  return (
    <form
      id="guest-info-form"
      onSubmit={handleSubmit}
      className="rounded-2xl border border-border bg-white p-6 sm:p-8"
    >
      <div>
        <h2 className="text-lg font-semibold">{t("guest.title")}</h2>
        <p className="mt-1 text-sm text-muted-foreground">
          {t("guest.desc")}
        </p>
      </div>

      <div className="mt-6 grid gap-4 sm:grid-cols-2">
        <div className="space-y-1.5">
          <Label htmlFor="gi-name">{t("guest.fullName")}</Label>
          <Input
            id="gi-name"
            value={values.full_name}
            onChange={handleChange("full_name")}
            aria-invalid={Boolean(fieldError(errors, "full_name"))}
            className="h-11"
          />
          {fieldError(errors, "full_name") && (
            <p className="text-xs text-red-600">
              {fieldError(errors, "full_name")}
            </p>
          )}
        </div>
        <div className="space-y-1.5">
          <Label htmlFor="gi-email">{t("guest.email")}</Label>
          <Input
            id="gi-email"
            type="email"
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
          <Label htmlFor="gi-phone">{t("guest.phone")}</Label>
          <Input
            id="gi-phone"
            type="tel"
            value={values.phone}
            onChange={handleChange("phone")}
            aria-invalid={Boolean(fieldError(errors, "phone"))}
            className="h-11"
          />
          {fieldError(errors, "phone") && (
            <p className="text-xs text-red-600">{fieldError(errors, "phone")}</p>
          )}
        </div>
        <div className="space-y-1.5">
          <Label htmlFor="gi-country">{t("guest.country")}</Label>
          <Select
            value={values.country}
            onValueChange={(v) => {
              setValues((current) => ({ ...current, country: v }));
              setErrors((current) => ({ ...current, country: undefined }));
            }}
          >
            <SelectTrigger id="gi-country" className="h-11">
              <SelectValue placeholder={t("guest.selectCountry")} />
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
        <div className="space-y-1.5">
          <Label htmlFor="gi-id-type">{t("guest.idType")}</Label>
          <Select
            value={values.id_type}
            onValueChange={(v) =>
              setValues((current) => ({ ...current, id_type: v }))
            }
          >
            <SelectTrigger id="gi-id-type" className="h-11">
              <SelectValue />
            </SelectTrigger>
            <SelectContent>
              <SelectItem value="national_id">{t("guest.nationalId")}</SelectItem>
              <SelectItem value="passport">{t("guest.passport")}</SelectItem>
            </SelectContent>
          </Select>
        </div>
        <div className="space-y-1.5">
          <Label htmlFor="gi-id-number">{t("guest.idNumber")}</Label>
          <Input
            id="gi-id-number"
            value={values.id_number}
            onChange={handleChange("id_number")}
            aria-invalid={Boolean(fieldError(errors, "id_number"))}
            className="h-11"
          />
          {fieldError(errors, "id_number") && (
            <p className="text-xs text-red-600">
              {fieldError(errors, "id_number")}
            </p>
          )}
        </div>
        <div className="space-y-1.5 sm:col-span-2">
          <Label htmlFor="gi-address">{t("guest.address")}</Label>
          <Input
            id="gi-address"
            value={values.address}
            onChange={handleChange("address")}
            className="h-11"
          />
        </div>
        <div className="space-y-1.5 sm:col-span-2">
          <Label htmlFor="gi-request">{t("guest.specialRequest")}</Label>
          <Textarea
            id="gi-request"
            rows={3}
            placeholder={t("guest.placeholder")}
            value={values.special_request}
            onChange={handleChange("special_request")}
          />
        </div>
      </div>

      <div className="mt-6 flex justify-end">
        <Button
          type="submit"
          size="pill-lg"
          disabled={saving}
          className="w-full sm:w-auto"
        >
          {saving && <Loader2 className="size-4 animate-spin" />}
          <Save /> {t("guest.save")}
        </Button>
      </div>
    </form>
  );
}