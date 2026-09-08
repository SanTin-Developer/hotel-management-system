import { useQuery } from "@tanstack/react-query";
import { Link } from "react-router-dom";
import { useState } from "react";
import { ArrowRight, CalendarDays } from "lucide-react";
import { Button } from "@/components/ui/button";
import { Loading } from "@/components/common/Loading";
import { ErrorMessage } from "@/components/common/ErrorMessage";
import { EmptyState } from "@/components/common/EmptyState";
import { formatCurrency } from "@/utils/formatCurrency";
import { formatDateRange } from "@/utils/formatDate";
import { BOOKING_STATUS_LABELS, BOOKING_STATUS_STYLES } from "@/constants/booking";
import { ROUTES } from "@/constants/routes";
import { fetchMyBookings } from "@/services/api/bookings";
import { useAuthStore } from "@/store/authStore";
import { useI18n } from "@/i18n";

const FILTERS = ["all", "pending", "confirmed", "in_house", "cancellation_requested", "completed", "cancelled"];

const FILTER_LABEL_KEYS = {
  cancellation_requested: "bookings.filterCancelRequested",
};

function filterLabelKey(value) {
  return (
    FILTER_LABEL_KEYS[value] ??
    `bookings.filter${value.charAt(0).toUpperCase() + value.slice(1)}`
  );
}

export function MyBookingsPage() {
  const guestId = useAuthStore((state) => state.guest?.id);
  const [filter, setFilter] = useState("all");
  const { t } = useI18n();

  const {
    data: items = [],
    isLoading,
    isError,
    refetch,
  } = useQuery({
    queryKey: ["my-bookings", guestId],
    queryFn: async () => {
      const { items } = await fetchMyBookings({
        guestId,
        params: { per_page: 50, sort: "newest" },
      });
      return items;
    },
    enabled: Boolean(guestId),
  });

  const visible =
    filter === "all" ? items : items.filter((b) => b.status === filter);

  return (
    <div>
      <div className="flex flex-wrap items-center justify-between gap-3">
        <div>
          <h1 className="text-2xl font-semibold tracking-tight">{t("bookings.myStays")}</h1>
          <p className="mt-1 text-sm text-muted-foreground">
            {t("bookings.desc")}
          </p>
        </div>
        <Link to={ROUTES.booking}>
          <Button size="pill" variant="gold">
            {t("bookings.newBooking")}
          </Button>
        </Link>
      </div>

      <div className="mt-6 flex flex-wrap gap-2">
        {FILTERS.map((value) => (
          <button
            key={value}
            type="button"
            onClick={() => setFilter(value)}
            className={`rounded-full border px-4 py-1.5 text-sm font-medium capitalize transition-colors ${
              filter === value
                ? "border-brand-900 bg-brand-900 text-white"
                : "border-border bg-white text-muted-foreground hover:border-brand-300"
            }`}
          >
            {t(filterLabelKey(value))}
          </button>
        ))}
      </div>

      <div className="mt-6">
        {isLoading ? (
          <Loading label={t("bookings.loading")} />
        ) : isError ? (
          <ErrorMessage message={t("bookings.loadError")} onRetry={refetch} />
        ) : visible.length === 0 ? (
          <EmptyState
            icon={<CalendarDays className="size-7" />}
            title={t("bookings.noStays")}
            description={
              filter === "all"
                ? t("bookings.emptyAll")
                : t("bookings.emptyFilter", { filter: t(filterLabelKey(filter)) })
            }
          />
        ) : (
          <ul className="space-y-3">
            {visible.map((booking) => {
              const nights = booking.booking_items?.length
                ? Math.max(...booking.booking_items.map((i) => i.nights ?? 1))
                : 1;
              return (
                <li key={booking.id}>
                  <Link
                    to={ROUTES.bookingDetails(booking.id)}
                    state={{ booking }}
                    className="group flex flex-col gap-4 rounded-2xl border border-border bg-white p-5 transition-all hover:-translate-y-0.5 hover:border-brand-300 hover:shadow-md sm:flex-row sm:items-center"
                  >
                    <div className="min-w-0 flex-1">
                      <div className="flex flex-wrap items-center gap-3">
                        <span className="font-mono text-sm font-semibold text-brand-900">
                          {booking.booking_code}
                        </span>
                        <span
                          className={`inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium ${BOOKING_STATUS_STYLES[booking.status] ?? "bg-muted text-muted-foreground"}`}
                        >
                          {t(BOOKING_STATUS_LABELS[booking.status] ?? booking.status)}
                        </span>
                      </div>
                      <p className="mt-1 flex items-center gap-1.5 text-sm text-muted-foreground">
                        <CalendarDays className="size-3.5" />
                        {formatDateRange(booking.check_in, booking.check_out)} ·{" "}
                        {nights} {t(nights === 1 ? "booking.night" : "booking.nights")} ·{" "}
                        {(booking.booking_items?.length ?? 1)}{" "}
                        {t((booking.booking_items?.length ?? 1) === 1 ? "payment.room" : "payment.roomsPlural")}
                      </p>
                    </div>
                    <div className="flex items-center gap-4">
                      <div className="text-right">
                        <p className="font-semibold text-brand-900">
                          {formatCurrency(booking.total_amount)}
                        </p>
                        {booking.deposit_amount > 0 && (
                          <p className="text-xs text-muted-foreground">
                            {t("bookings.depositDue", { rate: booking.deposit_rate })}
                          </p>
                        )}
                      </div>
                      <ArrowRight className="size-4 text-muted-foreground transition-transform group-hover:translate-x-1 group-hover:text-brand-900" />
                    </div>
                  </Link>
                </li>
              );
            })}
          </ul>
        )}
      </div>
    </div>
  );
}