import { Link } from "react-router-dom";
import { Check, ArrowRight } from "lucide-react";
import { Eyebrow } from "@/components/common/SectionHeading";
import { Button } from "@/components/ui/button";
import { useI18n } from "@/i18n";
import { IMAGES } from "@/lib/images";
import { ROUTES } from "@/constants/routes";

export function AmenitiesSection() {
  const { t } = useI18n();

  const items = [
    { key: "pool", label: t("amenities.pool") },
    { key: "spa", label: t("amenities.spa") },
    { key: "restaurant", label: t("amenities.restaurant") },
    { key: "gym", label: t("amenities.gym") },
  ];

  return (
    <section className="bg-cream py-20 sm:py-24">
      <div className="mx-auto grid max-w-7xl items-center gap-12 px-6 lg:grid-cols-2 lg:gap-16">
        <div className="order-2 lg:order-1">
          <img
            src={IMAGES.amenitiesPool}
            alt="Rooftop infinity pool"
            className="aspect-[4/3] w-full rounded-2xl object-cover shadow-2xl shadow-brand-950/15"
          />
        </div>

        <div className="order-1 lg:order-2">
          <Eyebrow>{t("amenities.eyebrow")}</Eyebrow>
          <h2 className="mt-4 text-3xl font-semibold leading-tight tracking-tight sm:text-4xl">
            {t("amenities.title")}
          </h2>
          <p className="mt-5 max-w-lg text-base leading-relaxed text-muted-foreground">
            {t("amenities.subtitle")}
          </p>

          <ul className="mt-8 space-y-4">
            {items.map((item) => (
              <li key={item.key} className="flex items-start gap-3">
                <span className="mt-0.5 grid size-6 shrink-0 place-items-center rounded-full bg-brand-900 text-white">
                  <Check className="size-3.5" />
                </span>
                <div>
                  <p className="font-medium">{item.label}</p>
                  <p className="text-sm text-muted-foreground">
                    {t(`amenities.${item.key}.desc`)}
                  </p>
                </div>
              </li>
            ))}
          </ul>

          <Link to={ROUTES.amenities} className="mt-9 inline-block">
            <Button variant="default" size="pill">
              {t("amenities.explore")}
              <ArrowRight />
            </Button>
          </Link>
        </div>
      </div>
    </section>
  );
}