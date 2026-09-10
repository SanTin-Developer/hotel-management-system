import { useRef, useState } from "react";
import { Link } from "react-router-dom";
import { Camera, KeyRound, Loader2, Save, Trash2 } from "lucide-react";
import { toast } from "sonner";
import { Button } from "@/components/ui/button";
import { Input } from "@/components/ui/input";
import { Label } from "@/components/ui/label";
import {
  Select,
  SelectContent,
  SelectItem,
  SelectTrigger,
  SelectValue,
} from "@/components/ui/select";
import { ImageCropDialog } from "@/components/profile/ImageCropDialog";
import { PhotoViewer } from "@/components/profile/PhotoViewer";
import { avatarFallback } from "@/lib/avatar";
import { useAuth } from "@/hooks/useAuth";
import { firstError, extractApiMessage } from "@/utils/validation";
import { ROUTES } from "@/constants/routes";
import { useI18n } from "@/i18n";

export function SettingsPage() {
  const { user, guest, updateProfile, uploadPhoto, removePhoto } = useAuth();
  const profile = guest ?? {};
  const fileInputRef = useRef(null);

  const [cropState, setCropState] = useState(null);
  const [viewerOpen, setViewerOpen] = useState(false);

  const [values, setValues] = useState({
    full_name: profile.full_name ?? user?.name ?? "",
    phone: profile.phone ?? "",
    country: profile.country ?? "",
    nationality: profile.nationality ?? "",
    gender: profile.gender ?? "other",
    date_of_birth: profile.date_of_birth ?? "",
    id_type: profile.id_type ?? "national_id",
    id_number: profile.id_number ?? "",
  });
  const [error, setError] = useState(null);
  const [saving, setSaving] = useState(false);
  const [photoBusy, setPhotoBusy] = useState(false);
  const { t } = useI18n();

  const handlePhotoChange = (event) => {
    const file = event.target.files?.[0];
    event.target.value = "";
    if (!file) return;

    const url = URL.createObjectURL(file);
    setCropState({ url, fileName: file.name });
  };

  const handlePhotoCrop = async (file) => {
    setCropState(null);
    setPhotoBusy(true);
    try {
      await uploadPhoto(file);
      toast.success(t("settings.photoUpdated"));
    } catch (err) {
      toast.error(
        firstError(err) ??
          extractApiMessage(err) ??
          t("settings.photoFailed")
      );
    } finally {
      setPhotoBusy(false);
    }
  };

  const handleRemovePhoto = async () => {
    setPhotoBusy(true);
    try {
      await removePhoto();
      toast.success(t("settings.photoRemoved"));
    } catch (err) {
      toast.error(
        extractApiMessage(err) ?? t("settings.photoRemoveFailed")
      );
    } finally {
      setPhotoBusy(false);
    }
  };

  const handleChange = (field, transform) => (event) => {
    const raw = event.target.value;
    setValues((current) => ({
      ...current,
      [field]: transform ? transform(raw) : raw,
    }));
    setError(null);
  };

  const handleSubmit = async (event) => {
    event.preventDefault();
    setSaving(true);
    setError(null);
    try {
      await updateProfile({
        full_name: values.full_name.trim(),
        phone: values.phone.trim() || null,
        country: values.country.trim() || null,
        nationality: values.nationality.trim() || null,
        gender: values.gender,
        date_of_birth: values.date_of_birth || null,
        id_type: values.id_type,
        id_number: values.id_number.trim() || null,
      });
      toast.success(t("settings.updated"));
    } catch (err) {
      setError(firstError(err) ?? extractApiMessage(err));
    } finally {
      setSaving(false);
    }
  };

  return (
    <div className="space-y-6">
      <div>
        <h1 className="text-2xl font-semibold tracking-tight">
          {t("settings.title")}
        </h1>
        <p className="mt-1 text-sm text-muted-foreground">
          {t("settings.desc")}
        </p>
      </div>

      <div className="rounded-2xl border border-border bg-white">
        <div className="flex items-center gap-4 border-b border-border px-6 py-5">
          <button
            type="button"
            onClick={() => profile.photo_url && setViewerOpen(true)}
            disabled={!profile.photo_url}
            title={profile.photo_url ? t("settings.viewPhoto") : undefined}
            className="relative grid size-16 shrink-0 place-items-center overflow-hidden rounded-full bg-brand-900 text-lg font-semibold text-white disabled:cursor-default"
          >
            {profile.photo_url ? (
              <img
                src={profile.photo_url}
                alt={profile.full_name ?? t("profile.avatarAlt")}
                className="size-full object-cover"
              />
            ) : (
              avatarFallback(profile.full_name ?? user?.name ?? t("profile.guest"))
            )}
            {photoBusy && (
              <span className="absolute inset-0 grid place-items-center bg-black/40">
                <Loader2 className="size-5 animate-spin text-white" />
              </span>
            )}
          </button>
          <div className="min-w-0">
            <p className="text-lg font-semibold">
              {profile.full_name ?? user?.name}
            </p>
            <p className="truncate text-sm text-muted-foreground">
              {profile.email ?? user?.email}
            </p>
            <div className="mt-2 flex flex-wrap items-center gap-2">
              <input
                ref={fileInputRef}
                type="file"
                accept="image/jpeg,image/png,image/webp,image/gif"
                className="hidden"
                onChange={handlePhotoChange}
              />
              <Button
                type="button"
                variant="outline"
                size="pill"
                onClick={() => fileInputRef.current?.click()}
                disabled={photoBusy}
              >
                {photoBusy ? (
                  <Loader2 className="size-4 animate-spin" />
                ) : (
                  <Camera />
                )}
                {t("settings.changePhoto")}
              </Button>
              {profile.photo_url && (
                <Button
                  type="button"
                  variant="ghost"
                  size="pill"
                  onClick={handleRemovePhoto}
                  disabled={photoBusy}
                >
                  <Trash2 /> {t("settings.remove")}
                </Button>
              )}
            </div>
          </div>
        </div>

        <form onSubmit={handleSubmit} className="px-6 py-6">
          {error && (
            <div className="mb-5 rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">
              {error}
            </div>
          )}

          <div className="grid gap-x-6 gap-y-5 sm:grid-cols-2">
            <div className="space-y-1.5">
              <Label htmlFor="settings-name">{t("settings.fullName")}</Label>
              <Input
                id="settings-name"
                className="h-11"
                value={values.full_name}
                onChange={handleChange("full_name")}
                required
              />
            </div>

            <div className="space-y-1.5">
              <Label htmlFor="settings-phone">{t("settings.phone")}</Label>
              <Input
                id="settings-phone"
                type="tel"
                className="h-11"
                value={values.phone}
                onChange={handleChange("phone")}
              />
            </div>

            <div className="space-y-1.5">
              <Label htmlFor="settings-country">{t("settings.country")}</Label>
              <Input
                id="settings-country"
                className="h-11"
                value={values.country}
                onChange={handleChange("country")}
              />
            </div>

            <div className="space-y-1.5">
              <Label htmlFor="settings-nationality">{t("settings.nationality")}</Label>
              <Input
                id="settings-nationality"
                className="h-11"
                value={values.nationality}
                onChange={handleChange("nationality")}
              />
            </div>

            <div className="space-y-1.5">
              <Label htmlFor="settings-gender">{t("settings.gender")}</Label>
              <Select
                value={values.gender}
                onValueChange={(value) =>
                  setValues((current) => ({ ...current, gender: value }))
                }
              >
                <SelectTrigger id="settings-gender" className="h-11">
                  <SelectValue />
                </SelectTrigger>
                <SelectContent>
                  <SelectItem value="male">{t("settings.male")}</SelectItem>
                  <SelectItem value="female">{t("settings.female")}</SelectItem>
                  <SelectItem value="other">{t("settings.genderOther")}</SelectItem>
                </SelectContent>
              </Select>
            </div>

            <div className="space-y-1.5">
              <Label htmlFor="settings-dob">{t("settings.dob")}</Label>
              <Input
                id="settings-dob"
                type="date"
                className="h-11"
                max={new Date().toISOString().split("T")[0]}
                value={values.date_of_birth}
                onChange={handleChange("date_of_birth")}
              />
            </div>

            <div className="space-y-1.5">
              <Label htmlFor="settings-id-type">{t("settings.idType")}</Label>
              <Select
                value={values.id_type}
                onValueChange={(value) =>
                  setValues((current) => ({ ...current, id_type: value }))
                }
              >
                <SelectTrigger id="settings-id-type" className="h-11">
                  <SelectValue />
                </SelectTrigger>
                <SelectContent>
                  <SelectItem value="national_id">{t("settings.nationalId")}</SelectItem>
                  <SelectItem value="passport">{t("settings.passport")}</SelectItem>
                </SelectContent>
              </Select>
            </div>

            <div className="space-y-1.5">
              <Label htmlFor="settings-id-number">{t("settings.idNumber")}</Label>
              <Input
                id="settings-id-number"
                className="h-11"
                value={values.id_number}
                onChange={handleChange("id_number", (v) =>
                  v.replace(/[^\w\d\-() ]/g, "")
                )}
              />
            </div>
          </div>

          <div className="mt-6 flex items-center justify-end gap-3 border-t border-border pt-5">
            <Button
              type="submit"
              size="pill"
              variant="dark"
              disabled={saving}
            >
              {saving && <Loader2 className="size-4 animate-spin" />}
              {!saving && <Save className="size-4" />}
              {t("settings.save")}
            </Button>
          </div>
        </form>
      </div>

      <div className="flex flex-col gap-4 rounded-2xl border border-border bg-white p-6 sm:flex-row sm:items-center sm:justify-between">
        <div className="flex items-center gap-3">
          <span className="grid size-11 place-items-center rounded-xl bg-brand-100 text-brand-800">
            <KeyRound className="size-5" />
          </span>
          <div>
            <p className="font-semibold">{t("settings.changePassword")}</p>
            <p className="text-sm text-muted-foreground">
              {t("settings.passwordDesc")}
            </p>
          </div>
        </div>
        <Link to={ROUTES.forgotPassword} className="shrink-0">
          <Button size="pill" variant="dark">
            {t("settings.updatePassword")}
          </Button>
        </Link>
      </div>

      <ImageCropDialog
        open={Boolean(cropState)}
        imageSrc={cropState?.url ?? null}
        fileName={cropState?.fileName}
        onCancel={() => {
          if (cropState) URL.revokeObjectURL(cropState.url);
          setCropState(null);
        }}
        onSave={handlePhotoCrop}
      />

      <PhotoViewer
        open={viewerOpen}
        src={profile.photo_url}
        alt={profile.full_name ?? t("profile.avatarAlt")}
        onOpenChange={setViewerOpen}
      />
    </div>
  );
}