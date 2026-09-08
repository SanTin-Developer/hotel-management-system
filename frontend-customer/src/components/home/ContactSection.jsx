import { MapPin, Phone, Mail, Clock } from "lucide-react";
import { SectionHeading } from "@/components/common/SectionHeading";
import { useI18n } from "@/i18n";
import { HOTEL_PHONE, HOTEL_EMAIL, HOTEL_MAP_EMBED } from "@/constants/routes";

export function ContactSection() {
  const { t, isKh } = useI18n();

  const rows = [
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
    { icon: Clock, label: t("contact.hours"), value: t("contact.hoursValue") },
  ];

  return (
    <section className="bg-cream py-20 sm:py-24">
      <div className="mx-auto max-w-7xl px-6">
        <SectionHeading
          eyebrow={t("contact.eyebrow")}
          title={t("contact.title")}
          subtitle={t("contact.subtitle")}
        />

        <div className="mt-14 grid gap-8 lg:grid-cols-[1fr_1.4fr]">
          <div className="grid content-start gap-4 sm:grid-cols-2 lg:grid-cols-1">
            {rows.map((row) => {
              const Icon = row.icon;
              const content = (
                <>
                  <span className="grid size-11 shrink-0 place-items-center rounded-full bg-brand-900 text-gold-400">
                    <Icon className="size-5" />
                  </span>
                  <div className="min-w-0">
                    <p className="text-xs font-semibold uppercase tracking-wider text-muted-foreground">
                      {row.label}
                    </p>
                    <p
                      className="mt-1 text-sm font-medium break-words"
                      data-kh={isKh ? "kh" : "en"}
                    >
                      {row.value}
                    </p>
                  </div>
                </>
              );
              return row.href ? (
                <a
                  key={row.label}
                  href={row.href}
                  className="flex items-center gap-4 rounded-2xl border border-border bg-white p-5 transition-colors hover:border-brand-300"
                >
                  {content}
                </a>
              ) : (
                <div
                  key={row.label}
                  className="flex items-center gap-4 rounded-2xl border border-border bg-white p-5"
                >
                  {content}
                </div>
              );
            })}
          </div>

          <div className="overflow-hidden rounded-2xl border border-border bg-white shadow-sm">
            <iframe
              title={t("contact.mapTitle")}
              src={HOTEL_MAP_EMBED}
              className="h-full min-h-72 w-full"
              loading="lazy"
              referrerPolicy="no-referrer-when-downgrade"
            />
          </div>
        </div>
      </div>
    </section>
  );
}
