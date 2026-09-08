import { Star, MessageSquareHeart } from "lucide-react";
import { PageHeader } from "@/components/common/PageHeader";
import { ReviewsSection } from "@/components/home/ReviewsSection";
import { Button } from "@/components/ui/button";
import { Link } from "react-router-dom";
import { useI18n } from "@/i18n";
import { ROUTES } from "@/constants/routes";

const STATS = [
  { value: "4.8 / 5", labelKey: "reviewsPage.rating" },
  { value: "2,400+", labelKey: "reviewsPage.verifiedCount" },
  { value: "94%", labelKey: "reviewsPage.returnRate" },
];

export function ReviewsPage() {
  const { t } = useI18n();
  return (
    <>
      <PageHeader
        eyebrow={t("reviewsPage.eyebrow")}
        title={t("reviewsPage.title")}
        subtitle={t("reviewsPage.subtitle")}
        crumb={t("reviewsPage.crumb")}
      />

      <section className="border-b border-border bg-white">
        <div className="mx-auto grid max-w-7xl gap-8 px-6 py-12 sm:grid-cols-3">
          {STATS.map((stat) => (
            <div key={stat.labelKey} className="text-center">
              <p className="flex items-center justify-center gap-1.5 text-3xl font-semibold tracking-tight text-brand-900">
                {stat.value.includes("/") && (
                  <Star className="size-6 fill-gold-400 text-gold-400" />
                )}
                {stat.value}
              </p>
              <p className="mt-1 text-sm text-muted-foreground">
                {t(stat.labelKey)}
              </p>
            </div>
          ))}
        </div>
      </section>

      <ReviewsSection limit={100} />

      <section className="bg-cream py-16 sm:py-20">
        <div className="mx-auto max-w-3xl px-6 text-center">
          <MessageSquareHeart className="mx-auto size-10 text-brand-800" />
          <h2 className="mt-4 text-2xl font-semibold tracking-tight sm:text-3xl">
            {t("reviewsPage.ctaTitle")}
          </h2>
          <p className="mx-auto mt-3 max-w-md text-sm text-muted-foreground">
            {t("reviewsPage.ctaDesc")}
          </p>
          <Link to={ROUTES.login} className="mt-6 inline-block">
            <Button size="pill-lg" variant="gold">
              {t("reviewsPage.signInToReview")}
            </Button>
          </Link>
        </div>
      </section>
    </>
  );
}