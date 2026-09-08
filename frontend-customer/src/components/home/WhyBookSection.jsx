import { BadgePercent, CalendarX2, Headset, Wifi } from "lucide-react";
import { SectionHeading } from "@/components/common/SectionHeading";
import { useI18n } from "@/i18n";

const FEATURES = [
  {
    key: "bestPrice",
    icon: BadgePercent,
    tint: "bg-brand-100 text-brand-800",
  },
  {
    key: "freeCancel",
    icon: CalendarX2,
    tint: "bg-gold-100 text-gold-700",
  },
  {
    key: "support",
    icon: Headset,
    tint: "bg-brand-100 text-brand-800",
  },
  {
    key: "wifi",
    icon: Wifi,
    tint: "bg-gold-100 text-gold-700",
  },
];

export function WhyBookSection() {
  const { t } = useI18n();

  return (
    <section className="bg-white py-20 sm:py-24">
      <div className="mx-auto max-w-7xl px-6">
        <SectionHeading
          eyebrow={t("whyBook.eyebrow")}
          title={t("whyBook.title")}
        />

        <div className="mt-14 grid gap-6 sm:grid-cols-2 lg:grid-cols-4">
          {FEATURES.map((feature) => {
            const Icon = feature.icon;
            return (
              <div
                key={feature.key}
                className="group rounded-2xl border border-border bg-cream/70 p-7 text-center transition-all duration-300 hover:-translate-y-1 hover:border-brand-200 hover:shadow-lg hover:shadow-brand-950/5"
              >
                <div
                  className={`mx-auto grid size-14 place-items-center rounded-full ${feature.tint}`}
                >
                  <Icon className="size-6" />
                </div>
                <h3 className="mt-5 text-lg font-semibold">
                  {t(`whyBook.${feature.key}.title`)}
                </h3>
                <p className="mt-2.5 text-sm leading-relaxed text-muted-foreground">
                  {t(`whyBook.${feature.key}.desc`)}
                </p>
              </div>
            );
          })}
        </div>
      </div>
    </section>
  );
}