import { useQuery } from "@tanstack/react-query";
import { Link } from "react-router-dom";
import { ArrowRight, Briefcase, CalendarDays, MapPin } from "lucide-react";
import { Button } from "@/components/ui/button";
import { Loading } from "@/components/common/Loading";
import { formatCurrency } from "@/utils/formatCurrency";
import { formatDateRange } from "@/utils/formatDate";
import { BOOKING_STATUS_STYLES, BOOKING_STATUS_LABELS } from "@/constants/booking";
import { ROUTES } from "@/constants/routes";
import { fetchMyBookings } from "@/services/api/bookings";
import { useAuthStore } from "@/store/authStore";
import { useI18n } from "@/i18n";

export function ProfileOverviewPage() {
  const guest = useAuthStore((state) => state.guest);
  const guestId = guest?.id;
  const { t } = useI18n();

  const { data: bookings = [], isLoading } = useQuery({
    queryKey: ["my-bookings", guestId],
    queryFn: async () => {
      const { items } = await fetchMyBookings({
        guestId,
        params: { per_page: 5, sort: "newest" },
      });
      return items;
    },
    enabled: Boolean(guestId),
  });

  if (isLoading) return <Loading label={t("profile.loading")} />;

  return (
    <div className="space-y-6">
      <div className="rounded-2xl border border-brand-100 bg-gradient-to-br from-brand-900 to-brand-950 p-8 text-white">
        <p className="text-sm text-brand-200">{t("profile.welcomeBack")}</p>
        <h1 className="mt-1 text-2xl font-semibold tracking-tight sm:text-3xl">
          {guest?.full_name ?? t("profile.guest")}
        </h1>
        <p className="mt-2 max-w-lg text-sm text-brand-200">
          {t("profile.overviewDesc")}
        </p>
        <div className="mt-6 flex flex-wrap gap-3">
          <Link to={ROUTES.booking}>
            <Button size="pill" variant="gold">
              {t("profile.bookStay")}
            </Button>
          </Link>
          <Link to={ROUTES.profileBookings}>
            <Button size="pill" variant="outlineLight">
              {t("profile.myStays")}
            </Button>
          </Link>
        </div>
      </div>

      <div className="grid gap-4 sm:grid-cols-3">
        <QuickCard
          icon={<Briefcase className="size-5" />}
          label={t("profile.totalStays")}
          value={String(bookings.length)}
        />
        <QuickCard
          icon={<CalendarDays className="size-5" />}
          label={t("profile.upcoming")}
          value={String(
            bookings.filter((b) => b.status === "confirmed").length
          )}
        />
        <QuickCard
          icon={<MapPin className="size-5" />}
          label={t("profile.memberSince")}
          value={guest?.email ? t("profile.guest") : t("profile.guest")}
        />
      </div>

      <div className="rounded-2xl border border-border bg-white">
        <div className="flex items-center justify-between border-b border-border px-6 py-4">
          <h2 className="font-semibold">{t("profile.recentStays")}</h2>
          <Link
            to={ROUTES.profileBookings}
            className="flex items-center gap-1 text-sm font-medium text-brand-800 hover:underline"
          >
            {t("profile.viewAll")} <ArrowRight className="size-4" />
          </Link>
        </div>

        {bookings.length === 0 ? (
          <div className="px-6 py-12 text-center">
            <p className="text-sm text-muted-foreground">
              {t("profile.noBookings")}
            </p>
            <Link to={ROUTES.booking}>
              <Button className="mt-4" size="pill" variant="dark">
                {t("profile.browseRooms")}
              </Button>
            </Link>
          </div>
        ) : (
          <ul className="divide-y divide-border">
            {bookings.map((booking) => (
              <li key={booking.id}>
                <Link
                  to={ROUTES.bookingDetails(booking.id)}
                  state={{ booking }}
                  className="flex items-center justify-between gap-4 px-6 py-4 transition-colors hover:bg-brand-50/50"
                >
                  <div className="min-w-0">
                    <p className="font-mono text-sm font-semibold">
                      {booking.booking_code}
                    </p>
                    <p className="mt-0.5 truncate text-sm text-muted-foreground">
                      {formatDateRange(booking.check_in, booking.check_out)}
                    </p>
                  </div>
                  <div className="flex items-center gap-4">
                    <span
                      className={`inline-flex items-center rounded-full px-2.5 py-1 text-xs font-medium ${BOOKING_STATUS_STYLES[booking.status] ?? "bg-muted text-muted-foreground"}`}
                    >
                      {t(BOOKING_STATUS_LABELS[booking.status] ?? booking.status)}
                    </span>
                    <span className="hidden font-semibold text-brand-900 sm:inline">
                      {formatCurrency(booking.total_amount)}
                    </span>
                  </div>
                </Link>
              </li>
            ))}
          </ul>
        )}
      </div>
    </div>
  );
}

function QuickCard({ icon, label, value }) {
  return (
    <div className="rounded-2xl border border-border bg-white p-5">
      <span className="grid size-10 place-items-center rounded-xl bg-brand-100 text-brand-800">
        {icon}
      </span>
      <p className="mt-3 text-2xl font-semibold tracking-tight">{value}</p>
      <p className="text-xs font-medium uppercase tracking-wider text-muted-foreground">
        {label}
      </p>
    </div>
  );
}