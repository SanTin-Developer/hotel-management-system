import { useQuery, useMutation, useQueryClient } from "@tanstack/react-query";
import { Link, useLocation, useParams } from "react-router-dom";
import { useState } from "react";
import {
  ArrowLeft,
  BedDouble,
  CalendarDays,
  CalendarX2,
  CreditCard,
  LoaderCircle,
} from "lucide-react";
import { Button } from "@/components/ui/button";
import {
  Dialog,
  DialogContent,
  DialogDescription,
  DialogFooter,
  DialogHeader,
  DialogTitle,
} from "@/components/ui/dialog";
import { Loading } from "@/components/common/Loading";
import { ErrorMessage } from "@/components/common/ErrorMessage";
import { formatCurrency } from "@/utils/formatCurrency";
import { formatDateRange } from "@/utils/formatDate";
import { BOOKING_STATUS_LABELS, BOOKING_STATUS_STYLES } from "@/constants/booking";
import { ROUTES, HOTEL_PHONE } from "@/constants/routes";
import {
  fetchMyBookings,
  requestBookingCancellation,
} from "@/services/api/bookings";
import { useAuthStore } from "@/store/authStore";
import { useI18n } from "@/i18n";

export function BookingDetailsPage() {
  const { id } = useParams();
  const location = useLocation();
  const guestId = useAuthStore((state) => state.guest?.id);
  const locationBooking = location.state?.booking;
  const { t } = useI18n();
  const queryClient = useQueryClient();
  const [cancelOpen, setCancelOpen] = useState(false);
  const [cancelNotice, setCancelNotice] = useState(null);

  const { data: booking = locationBooking, isLoading, isError, refetch } =
    useQuery({
      queryKey: ["booking-detail", id, guestId],
      queryFn: async () => {
        if (locationBooking) return locationBooking;
        const { items } = await fetchMyBookings({ guestId, params: { per_page: 50 } });
        return items.find((item) => String(item.id) === String(id)) ?? null;
      },
      enabled: Boolean(guestId) && Boolean(id),
    });

  const cancelMutation = useMutation({
    mutationFn: () => requestBookingCancellation(booking?.id),
    onSuccess: () => {
      setCancelOpen(false);
      setCancelNotice("success");
      queryClient.invalidateQueries({ queryKey: ["booking-detail"] });
      queryClient.invalidateQueries({ queryKey: ["my-bookings"] });
    },
    onError: () => {
      setCancelOpen(false);
      setCancelNotice("error");
    },
  });

  if (isLoading) return <Loading label={t("bookingDetails.loading")} />;

  if (!booking) {
    return (
      <div>
        <ErrorMessage title={t("bookingDetails.notFoundTitle")} message={t("bookingDetails.notFoundMsg")} />
        <Link to={ROUTES.profileBookings} className="mt-6 inline-block">
          <Button size="pill" variant="dark">
            <ArrowLeft /> {t("bookingDetails.backToStays")}
          </Button>
        </Link>
      </div>
    );
  }

  const nights = booking.booking_items?.length
    ? Math.max(...booking.booking_items.map((i) => i.nights ?? 1))
    : 1;

  const canRequestCancellation =
    ["pending", "confirmed"].includes(booking.status) &&
    Boolean(booking.cancellation_refundable);

  const isWithinCancelWindow =
    ["pending", "confirmed"].includes(booking.status) &&
    !booking.cancellation_refundable;

  const isCancelPending = booking.status === "cancellation_requested";

  return (
    <div>
      <Link to={ROUTES.profileBookings} className="inline-flex items-center gap-1.5 text-sm font-medium text-brand-800 hover:underline">
        <ArrowLeft className="size-4" /> {t("bookingDetails.backToStays")}
      </Link>

      <div className="mt-4 overflow-hidden rounded-2xl border border-border bg-white">
        <div className="flex flex-wrap items-center justify-between gap-3 bg-gradient-to-br from-brand-900 to-brand-950 px-6 py-6 text-white">
          <div>
            <p className="text-xs uppercase tracking-wider text-brand-300">
              {t("bookingDetails.reference")}
            </p>
            <p className="mt-1 font-mono text-xl font-semibold tracking-wider">
              {booking.booking_code}
            </p>
          </div>
          <span
            className={`inline-flex items-center rounded-full px-3 py-1 text-xs font-medium ${BOOKING_STATUS_STYLES[booking.status] ?? "bg-white/20 text-white"}`}
          >
            {BOOKING_STATUS_LABELS[booking.status] ?? booking.status}
          </span>
        </div>

        <div className="divide-y divide-border">
          <div className="grid gap-5 px-6 py-6 sm:grid-cols-3">
            <div>
              <p className="flex items-center gap-1.5 text-xs uppercase tracking-wider text-muted-foreground">
                <CalendarDays className="size-3.5" /> {t("bookingDetails.stay")}
              </p>
              <p className="mt-1.5 font-semibold">
                {formatDateRange(booking.check_in, booking.check_out)}
              </p>
              <p className="text-xs text-muted-foreground">
                {nights} {t(nights === 1 ? "booking.night" : "booking.nights")} · {booking.adults ?? 1} {t((booking.adults ?? 1) === 1 ? "booking.adult" : "booking.adults")}
                {booking.children ? ` · ${booking.children} child(ren)` : ""}
              </p>
            </div>
            <div>
              <p className="flex items-center gap-1.5 text-xs uppercase tracking-wider text-muted-foreground">
                <CreditCard className="size-3.5" /> {t("bookingDetails.payment")}
              </p>
              <p className="mt-1.5 text-lg font-bold text-brand-900">
                {formatCurrency(booking.total_amount)}
              </p>
              {booking.deposit_amount > 0 && (
                <p className="text-xs text-muted-foreground">
                  {t("bookingDetails.depositDue", { rate: booking.deposit_rate, deposit: formatCurrency(booking.deposit_amount) })}
                </p>
              )}
            </div>
            <div>
              <p className="text-xs uppercase tracking-wider text-muted-foreground">
                {t("bookingDetails.source")}
              </p>
              <p className="mt-1.5 text-sm font-medium capitalize">
                {booking.booking_source ?? "website"}
              </p>
            </div>
          </div>

          {booking.rooms?.length > 0 && (
            <div className="px-6 py-6">
              <p className="flex items-center gap-1.5 text-xs uppercase tracking-wider text-muted-foreground">
                <BedDouble className="size-3.5" /> {t("bookingDetails.rooms")}
              </p>
              <ul className="mt-3 space-y-2">
                {booking.rooms.map((room) => (
                  <li
                    key={room.id}
                    className="flex items-center gap-3 rounded-xl border border-border px-4 py-3 text-sm"
                  >
                    <span className="grid size-8 place-items-center rounded-full bg-brand-100 text-brand-800">
                      <BedDouble className="size-4" />
                    </span>
                    <div className="flex-1">
                      <p className="font-semibold">{t("bookingDetails.roomNumber", { number: room.room_number })}</p>
                      <p className="text-xs text-muted-foreground">
                        {room.room_type?.name ?? t("bookingDetails.roomType", { id: room.room_type?.name ?? room.room_type_id })} · {t("bookingDetails.floor", { floor: room.floor })}
                      </p>
                    </div>
                    {(booking.booking_items ?? []).find((i) => i.room_id === room.id) && (
                      <span className="text-sm font-medium text-brand-900">
                        {formatCurrency(
                          (booking.booking_items ?? []).find((i) => i.room_id === room.id)?.subtotal
                        )}
                      </span>
                    )}
                  </li>
                ))}
              </ul>
            </div>
          )}

          <div className="px-6 py-5 text-sm">
            <p className="font-medium">{t("bookingDetails.help")}</p>
            <p className="mt-1 text-muted-foreground">
              {t("bookingDetails.callDesk", { phone: HOTEL_PHONE })} <Link to={ROUTES.contact} className="text-brand-800 hover:underline">
                {t("bookingDetails.sendMessage")}
              </Link> {t("bookingDetails.open247")}
            </p>
          </div>
        </div>
      </div>

      {isCancelPending && (
        <div className="mt-4 flex items-start gap-3 rounded-2xl border border-blue-200 bg-blue-50 px-5 py-4 text-sm text-blue-900">
          <CalendarX2 className="mt-0.5 size-4 shrink-0" />
          <p>{t("bookingDetails.cancellationRequestedNote")}</p>
        </div>
      )}

      {cancelNotice === "success" && !isCancelPending && (
        <div className="mt-4 flex items-start gap-3 rounded-2xl border border-brand-200 bg-brand-50 px-5 py-4 text-sm text-brand-900">
          <CalendarX2 className="mt-0.5 size-4 shrink-0" />
          <p>{t("bookingDetails.cancelSuccess")}</p>
        </div>
      )}

      {cancelNotice === "error" && (
        <div className="mt-4">
          <ErrorMessage
            message={t("bookingDetails.cancelError")}
            onRetry={() => setCancelOpen(true)}
          />
        </div>
      )}

      {canRequestCancellation && (
        <div className="mt-4 flex flex-wrap items-center justify-between gap-3 rounded-2xl border border-border bg-white px-5 py-4">
          <div>
            <p className="font-medium">{t("bookingDetails.cancelTitle")}</p>
            <p className="mt-0.5 text-sm text-muted-foreground">
              {t("bookingDetails.cancelRefundNotice")}
            </p>
          </div>
          <Button
            type="button"
            variant="outline"
            size="pill"
            className="border-red-200 text-red-700 hover:bg-red-50"
            onClick={() => setCancelOpen(true)}
          >
            {t("bookingDetails.cancelRequestBtn")}
          </Button>
        </div>
      )}

      {isWithinCancelWindow && (
        <div className="mt-4 flex items-start gap-3 rounded-2xl border border-amber-200 bg-amber-50 px-5 py-4 text-sm text-amber-900">
          <CalendarX2 className="mt-0.5 size-4 shrink-0" />
          <p>{t("bookingDetails.cancelBlocked")}</p>
        </div>
      )}

      <Dialog open={cancelOpen} onOpenChange={setCancelOpen}>
        <DialogContent className="sm:max-w-md">
          <DialogHeader>
            <DialogTitle>{t("bookingDetails.cancelTitle")}</DialogTitle>
            <DialogDescription>
              {t("bookingDetails.cancelDesc")}
            </DialogDescription>
          </DialogHeader>
          <DialogFooter>
            <Button
              variant="outline"
              onClick={() => setCancelOpen(false)}
              disabled={cancelMutation.isPending}
            >
              {t("common.close")}
            </Button>
            <Button
              variant="destructive"
              onClick={() => cancelMutation.mutate()}
              disabled={cancelMutation.isPending}
            >
              {cancelMutation.isPending && <LoaderCircle className="size-4 animate-spin" />}
              {t("bookingDetails.cancelConfirm")}
            </Button>
          </DialogFooter>
        </DialogContent>
      </Dialog>

      {isError && (
        <div className="mt-4">
          <ErrorMessage
            message={t("bookingDetails.refreshError")}
            onRetry={refetch}
          />
        </div>
      )}
    </div>
  );
}