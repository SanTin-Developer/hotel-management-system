import { useQuery } from "@tanstack/react-query";
import { Link } from "react-router-dom";
import { Copy, Check, TicketPercent, ArrowRight } from "lucide-react";
import { useState } from "react";
import { PageHeader } from "@/components/common/PageHeader";
import { Loading } from "@/components/common/Loading";
import { ErrorMessage } from "@/components/common/ErrorMessage";
import { Button } from "@/components/ui/button";
import { useI18n } from "@/i18n";
import { fetchActiveCoupons } from "@/services/api/coupons";
import { formatDate } from "@/utils/formatDate";
import { formatCurrency } from "@/utils/formatCurrency";
import { toast } from "sonner";
import { ROUTES, HOTEL_NAME } from "@/constants/routes";

function discountLabel(coupon) {
  if (coupon.discount_type === "percentage") {
    return `${coupon.discount_value}% OFF`;
  }
  return `${formatCurrency(coupon.discount_value)} OFF`;
}

export function OffersPage() {
  const { t } = useI18n();

  const {
    data = null,
    isLoading,
    isError,
    refetch,
  } = useQuery({
    queryKey: ["offers"],
    queryFn: fetchActiveCoupons,
  });

  const coupons = Array.isArray(data) ? data : [];
  const noCoupons = Array.isArray(data) && data.length === 0;

  return (
    <>
      <PageHeader
        eyebrow={t("offers.eyebrow")}
        title={t("offers.title")}
        subtitle={t("offers.subtitle")}
        crumb={t("offers.crumb")}
      />

      <section className="bg-white py-14 sm:py-16">
        <div className="mx-auto max-w-7xl px-6">
          {isLoading ? (
            <Loading label={t("common.loading")} />
          ) : isError ? (
            <ErrorMessage message={t("offers.loadError")} onRetry={refetch} />
          ) : noCoupons ? (
            <div className="rounded-2xl border border-dashed border-brand-300 bg-brand-50 px-6 py-16 text-center">
              <TicketPercent className="mx-auto size-10 text-brand-700" />
              <h2 className="mt-4 text-xl font-semibold">
                {t("offers.emptyTitle")}
              </h2>
              <p className="mx-auto mt-2 max-w-md text-sm text-muted-foreground">
                {t("offers.emptySubtitle")}
              </p>
            </div>
          ) : (
            <div className="grid gap-6 sm:grid-cols-2 lg:grid-cols-3">
              {coupons.map((coupon) => (
                <CouponCard key={coupon.id} coupon={coupon} />
              ))}
            </div>
          )}
        </div>
      </section>

      <section className="bg-brand-950 py-16 text-white">
        <div className="mx-auto flex max-w-7xl flex-col items-center justify-between gap-6 px-6 text-center lg:flex-row lg:text-left">
          <div>
            <h2 className="text-2xl font-semibold tracking-tight">
              {t("offers.directTitle")}
            </h2>
            <p className="mt-2 max-w-xl text-brand-200">
              {t("offers.directDesc")}
            </p>
          </div>
          <Link to={ROUTES.booking}>
            <Button size="pill-lg" variant="gold">
              {t("offers.bookNow")} <ArrowRight />
            </Button>
          </Link>
        </div>
      </section>
    </>
  );
}

function CouponCard({ coupon }) {
  const { t } = useI18n();
  const [copied, setCopied] = useState(false);

  const copy = async () => {
    try {
      await navigator.clipboard.writeText(coupon.code);
      setCopied(true);
      toast.success(t("offers.copied", { code: coupon.code }));
      setTimeout(() => setCopied(false), 2000);
    } catch {
      toast.error(t("offers.copyError"));
    }
  };

  const dark = coupon.discount_type === "percentage";

  return (
    <div
      className={`group relative overflow-hidden rounded-2xl border p-6 transition-all hover:-translate-y-1 hover:shadow-lg ${
        dark
          ? "border-brand-900 bg-brand-950 text-white"
          : "border-brand-200 bg-brand-50 text-brand-950"
      }`}
    >
      <div className="flex items-center justify-between">
        <span
          className={`rounded-full px-3 py-1 text-xs font-bold tracking-wide ${
            dark ? "bg-gold-400 text-brand-950" : "bg-brand-900 text-white"
          }`}
        >
          {discountLabel(coupon)}
        </span>
        <span className="font-mono text-xs tracking-widest opacity-70">
          {coupon.code}
        </span>
      </div>

      <p className="mt-5 text-lg font-semibold leading-snug">
        {discountLabel(coupon)} {t("offers.onStay", { hotel: HOTEL_NAME })}.
      </p>

      <div className="mt-3 space-y-1 text-xs opacity-80">
        {coupon.start_date && coupon.end_date && (
          <p>{t("offers.validUntil", { date: formatDate(coupon.end_date) })}</p>
        )}
        {Number(coupon.min_amount) > 0 && (
          <p>{t("offers.minBooking", { amount: formatCurrency(coupon.min_amount) })}</p>
        )}
      </div>

      <button
        type="button"
        onClick={copy}
        className={`mt-5 flex w-full items-center justify-center gap-2 rounded-full py-2.5 text-sm font-semibold transition-colors ${
          dark
            ? "bg-white text-brand-950 hover:bg-brand-100"
            : "bg-brand-900 text-white hover:bg-brand-800"
        }`}
      >
        {copied ? <Check className="size-4" /> : <Copy className="size-4" />}
        {copied ? t("offers.copied") : t("offers.copyPromo")}
      </button>
    </div>
  );
}
