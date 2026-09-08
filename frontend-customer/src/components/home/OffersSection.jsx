import { useQuery } from "@tanstack/react-query";
import { Link } from "react-router-dom";
import { ArrowRight, Tag, Copy } from "lucide-react";
import { toast } from "sonner";
import { SectionHeading } from "@/components/common/SectionHeading";
import { Loading } from "@/components/common/Loading";
import { Button } from "@/components/ui/button";
import { useI18n } from "@/i18n";
import { fetchActiveCoupons } from "@/services/api/coupons";
import { ROUTES } from "@/constants/routes";
import { formatDate } from "@/utils/formatDate";
import { formatCurrency } from "@/utils/formatCurrency";

export function OffersSection({ limit = 3, showButton = true }) {
  const { t } = useI18n();

  const { data: coupons = [], isPending } = useQuery({
    queryKey: ["home-offers"],
    queryFn: fetchActiveCoupons,
    staleTime: 10 * 60_000,
  });

  const discountLabel = (coupon) => {
    if (coupon.discount_type === "percentage") {
      return t("offers.off", { value: coupon.discount_value });
    }
    return t("offers.offAmount", { value: formatCurrency(coupon.discount_value) });
  };

  const copyCode = (code) => {
    navigator.clipboard?.writeText(code).catch(() => {});
    toast.success(t("offers.codeCopied"));
  };

  const visible = coupons.slice(0, limit);

  return (
    <section className="bg-brand-950 py-20 text-white sm:py-24">
      <div className="mx-auto max-w-7xl px-6">
        <SectionHeading
          eyebrow={t("offers.eyebrow")}
          title={t("offers.title")}
          subtitle={t("offers.subtitle")}
          className="[&_h2]:text-white [&_p]:text-brand-200"
        />

        {isPending ? (
          <Loading label={t("common.loading")} className="text-brand-200" />
        ) : visible.length === 0 ? (
          <p className="mt-12 text-center text-brand-200">
            {t("common.noResults")}
          </p>
        ) : (
          <div className="mt-14 grid gap-6 md:grid-cols-2 lg:grid-cols-3">
            {visible.map((coupon) => (
              <div
                key={coupon.id}
                className="flex flex-col rounded-2xl border border-brand-800 bg-brand-900/60 p-7 backdrop-blur transition-colors hover:border-gold-400/60"
              >
                <div className="flex items-center justify-between">
                  <span className="grid size-11 place-items-center rounded-full bg-gold-400 text-brand-950">
                    <Tag className="size-5" />
                  </span>
                  <span className="rounded-full border border-gold-400/50 px-3 py-1 text-xs font-medium text-gold-300">
                    {t("offers.validUntil")} {formatDate(coupon.end_date)}
                  </span>
                </div>
                <h3 className="mt-5 text-2xl font-semibold tracking-tight">
                  {discountLabel(coupon)}
                </h3>
                <p className="mt-2 text-sm text-brand-200">
                  {t("offers.useCode")}{" "}
                  <span className="font-semibold text-white">{coupon.code}</span>
                </p>
                {coupon.min_amount ? (
                  <p className="mt-1 text-xs text-brand-300">
                    {t("offers.minBooking", { amount: formatCurrency(coupon.min_amount) })}
                  </p>
                ) : null}
                <button
                  type="button"
                  onClick={() => copyCode(coupon.code)}
                  className="mt-6 inline-flex items-center gap-2 justify-self-start rounded-full bg-white/10 px-4 py-2 text-sm font-medium transition-colors hover:bg-white/20"
                >
                  <Copy className="size-4" />
                  {coupon.code}
                </button>
              </div>
            ))}
          </div>
        )}

        {showButton && visible.length > 0 && (
          <div className="mt-12 flex justify-center">
            <Link to={ROUTES.offers}>
              <Button variant="gold" size="pill-lg">
                {t("offers.viewAll")}
                <ArrowRight />
              </Button>
            </Link>
          </div>
        )}
      </div>
    </section>
  );
}