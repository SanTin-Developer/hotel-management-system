import { Link } from "react-router-dom";
import { Sparkles } from "lucide-react";
import { Button } from "@/components/ui/button";
import { Eyebrow } from "@/components/common/SectionHeading";
import { useI18n } from "@/i18n";
import { IMAGES } from "@/lib/images";
import { ROUTES } from "@/constants/routes";

export function WelcomeSection() {
  const { t } = useI18n();

  return (
    <section className="bg-cream py-24 sm:py-28">
      <div className="mx-auto grid max-w-7xl items-center gap-12 px-6 lg:grid-cols-2 lg:gap-16">
        <div className="relative">
          <img
            src={IMAGES.welcome}
            alt="General Manager portrait"
            className="aspect-[4/5] w-full max-w-md rounded-2xl object-cover shadow-2xl shadow-brand-950/20"
          />
          <div className="absolute -bottom-6 -right-4 max-w-[16rem] rounded-2xl border border-brand-100 bg-white p-5 shadow-xl sm:-right-8">
            <Sparkles className="size-6 text-gold-500" />
            <p className="mt-2 text-sm font-semibold text-brand-900">
              {t("welcome.gmName")}
            </p>
            <p className="mt-0.5 text-xs text-muted-foreground">
              {t("welcome.gmAlt")}
            </p>
          </div>
        </div>

        <div>
          <Eyebrow>{t("welcome.eyebrow")}</Eyebrow>
          <h2 className="mt-4 text-3xl font-semibold leading-tight tracking-tight sm:text-4xl">
            {t("welcome.title")}
          </h2>
          <p className="mt-6 text-base leading-relaxed text-muted-foreground">
            {t("welcome.body")}
          </p>
          <p className="mt-4 text-base leading-relaxed text-muted-foreground">
            {t("welcome.body2")}
          </p>
          <Link to={ROUTES.about} className="mt-8 inline-block">
            <Button variant="default" size="pill">
              {t("welcome.readMore")}
            </Button>
          </Link>
        </div>
      </div>
    </section>
  );
}
