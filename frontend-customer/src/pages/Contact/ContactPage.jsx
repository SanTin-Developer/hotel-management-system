import { useState } from "react";
import { Loader2, Send, MapPin, Phone, Mail, Clock } from "lucide-react";
import { PageHeader } from "@/components/common/PageHeader";
import { Button } from "@/components/ui/button";
import { Input } from "@/components/ui/input";
import { Label } from "@/components/ui/label";
import { Textarea } from "@/components/ui/textarea";
import { HOTEL_PHONE, HOTEL_EMAIL, HOTEL_MAP_EMBED } from "@/constants/routes";
import { useI18n } from "@/i18n";
import { sendContactMessage } from "@/services/email";
import { toast } from "sonner";

export function ContactPage() {
  const { t } = useI18n();
  const [values, setValues] = useState({
    name: "",
    email: "",
    subject: t("contact.subject"),
    message: "",
  });
  const [sending, setSending] = useState(false);

  const handleSubmit = async (event) => {
    event.preventDefault();
    setSending(true);

    try {
      await sendContactMessage({
        name: values.name,
        email: values.email,
        subject: `${values.subject} — ${values.name}`,
        message: values.message,
      });
      toast.success(t("contact.sendSuccess"));
      setValues((v) => ({
        ...v,
        name: "",
        email: "",
        subject: t("contact.subject"),
        message: "",
      }));
    } catch (error) {
      const detail = error?.text || error?.message;
      toast.error(
        detail ? `${t("contact.sendError")} (${detail})` : t("contact.sendError"),
      );
    } finally {
      setSending(false);
    }
  };

  const DETAILS = [
    {
      icon: MapPin,
      label: t("contact.address"),
      value: t("contact.addressValue"),
    },
    {
      icon: Phone,
      label: t("contact.phone"),
      value: HOTEL_PHONE,
      href: `tel:${HOTEL_PHONE.replace(/\s/g, "")}`,
    },
    {
      icon: Mail,
      label: t("contact.email"),
      value: HOTEL_EMAIL,
      href: `mailto:${HOTEL_EMAIL}`,
    },
    {
      icon: Clock,
      label: t("contact.hours"),
      value: t("contact.hoursValue"),
    },
  ];

  return (
    <>
      <PageHeader
        eyebrow={t("contact.page.title")}
        title={t("contact.page.subtitle")}
        subtitle={t("contact.page.desc")}
        crumb={t("contact.page.eyebrow")}
      />

      <section className="bg-white py-16">
        <div className="mx-auto grid max-w-7xl gap-10 px-6 lg:grid-cols-[1fr_1.3fr]">
          <div>
            <div className="grid gap-4 sm:grid-cols-2 lg:grid-cols-1">
              {DETAILS.map((detail) => {
                const Icon = detail.icon;
                const inner = (
                  <>
                    <span className="grid size-11 shrink-0 place-items-center rounded-full bg-brand-900 text-gold-400">
                      <Icon className="size-5" />
                    </span>
                    <div>
                      <p className="text-xs font-semibold uppercase tracking-wider text-muted-foreground">
                        {detail.label}
                      </p>
                      <p className="mt-1 text-sm font-medium">{detail.value}</p>
                    </div>
                  </>
                );
                return detail.href ? (
                  <a
                    key={detail.label}
                    href={detail.href}
                    className="flex items-center gap-4 rounded-2xl border border-border bg-white p-5 transition-colors hover:border-brand-300"
                  >
                    {inner}
                  </a>
                ) : (
                  <div
                    key={detail.label}
                    className="flex items-center gap-4 rounded-2xl border border-border bg-white p-5"
                  >
                    {inner}
                  </div>
                );
              })}
            </div>

            <div className="mt-6 overflow-hidden rounded-2xl border border-border shadow-sm">
              <iframe
                title={t("contact.mapTitle")}
                src={HOTEL_MAP_EMBED}
                className="h-64 w-full"
                loading="lazy"
                referrerPolicy="no-referrer-when-downgrade"
              />
            </div>
          </div>

          <form
            onSubmit={handleSubmit}
            className="rounded-2xl border border-border bg-white p-6 sm:p-8"
          >
            <h2 className="text-xl font-semibold tracking-tight">
              {t("contact.sendTitle")}
            </h2>
            <p className="mt-1 text-sm text-muted-foreground">
              {t("contact.whatsapp")} {HOTEL_PHONE}.
            </p>

            <div className="mt-6 grid gap-4 sm:grid-cols-2">
              <div className="space-y-1.5">
                <Label htmlFor="c-name">{t("contact.yourName")}</Label>
                <Input
                  id="c-name"
                  required
                  className="h-11"
                  value={values.name}
                  onChange={(e) =>
                    setValues((v) => ({ ...v, name: e.target.value }))
                  }
                />
              </div>
              <div className="space-y-1.5">
                <Label htmlFor="c-email">{t("contact.email")}</Label>
                <Input
                  id="c-email"
                  type="email"
                  required
                  className="h-11"
                  value={values.email}
                  onChange={(e) =>
                    setValues((v) => ({ ...v, email: e.target.value }))
                  }
                />
              </div>
              <div className="space-y-1.5 sm:col-span-2">
                <Label htmlFor="c-subject">{t("contact.subject")}</Label>
                <Input
                  id="c-subject"
                  className="h-11"
                  value={values.subject}
                  onChange={(e) =>
                    setValues((v) => ({ ...v, subject: e.target.value }))
                  }
                />
              </div>
              <div className="space-y-1.5 sm:col-span-2">
                <Label htmlFor="c-message">{t("contact.message")}</Label>
                <Textarea
                  id="c-message"
                  rows={6}
                  required
                  placeholder={t("contact.messagePlaceholder")}
                  value={values.message}
                  onChange={(e) =>
                    setValues((v) => ({ ...v, message: e.target.value }))
                  }
                />
              </div>
            </div>

            <Button
              type="submit"
              size="pill-lg"
              className="mt-6"
              disabled={sending}
            >
              {sending ? <Loader2 className="size-4 animate-spin" /> : <Send />}
              {t("contact.send")}
            </Button>
          </form>
        </div>
      </section>
    </>
  );
}
