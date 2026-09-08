import { useRef, useState } from "react";
import { Link, Navigate, useLocation, useNavigate } from "react-router-dom";
import { motion } from "framer-motion";
import {
  Check,
  PartyPopper,
  CalendarDays,
  MapPin,
  Phone,
  Home,
  BedDouble,
  ArrowRight,
  FileDown,
  Loader2,
} from "lucide-react";
import { Button } from "@/components/ui/button";
import { formatCurrency } from "@/utils/formatCurrency";
import { formatDateRange } from "@/utils/formatDate";
import { downloadInvoice } from "@/utils/invoice";
import { Invoice } from "@/components/booking/Invoice";
import { BOOKING_STATUS_LABELS } from "@/constants/booking";
import { ROUTES, HOTEL_PHONE, HOTEL_ADDRESS } from "@/constants/routes";
import { toast } from "sonner";
import { useI18n } from "@/i18n";

export function BookingConfirmationPage() {
  const { t } = useI18n();
  const navigate = useNavigate();
  const location = useLocation();
  const invoiceRef = useRef(null);
  const [downloading, setDownloading] = useState(false);
  const booking = location.state?.booking;
  const depositPaid = location.state?.depositPaid ?? false;
  const receipt = booking ?? null;

  if (!receipt) return <Navigate to={ROUTES.home} replace />;

  const nights =
    receipt.booking_items?.length
      ? Math.max(...receipt.booking_items.map((item) => item.nights ?? 1))
      : 1;

  const handleDownloadInvoice = async () => {
    setDownloading(true);
    try {
      await downloadInvoice(
        invoiceRef.current,
        `invoice-${receipt.booking_code}.pdf`
      );
    } catch {
      toast.error(t("confirm.invoiceError"));
    } finally {
      setDownloading(false);
    }
  };

  return (
    <section className="min-h-[70vh] bg-cream py-16">
      <div ref={invoiceRef}>
        <Invoice booking={receipt} depositPaid={depositPaid} />
      </div>
      <div className="mx-auto max-w-2xl px-6">
        <div className="text-center">
          <motion.div
            initial={{ scale: 0.7, opacity: 0 }}
            animate={{ scale: 1, opacity: 1 }}
            transition={{ type: "spring", stiffness: 260, damping: 20 }}
            className="mx-auto grid size-20 place-items-center rounded-full bg-brand-900 text-white shadow-lg shadow-brand-900/30"
          >
            <Check className="size-10" strokeWidth={3} />
          </motion.div>
          <p className="mt-6 inline-flex items-center gap-2 text-sm font-medium text-brand-800">
            <PartyPopper className="size-4" /> {t("confirm.reserved")}
          </p>
          <h1 className="mt-2 text-3xl font-semibold tracking-tight sm:text-4xl">
            {t("confirm.seeYou")}
          </h1>
          <p className="mx-auto mt-3 max-w-md text-pretty text-muted-foreground">
            {t("confirm.body")}
          </p>
        </div>

        <div className="mt-10 overflow-hidden rounded-2xl border border-border bg-white">
          <div className="flex flex-wrap items-center justify-between gap-3 border-b border-border bg-brand-950 px-6 py-5 text-white">
            <div>
                <p className="text-xs uppercase tracking-wider text-brand-300">
                  {t("confirm.reference")}
                </p>
              <p className="mt-0.5 font-mono text-lg font-semibold tracking-wider">
                {receipt.booking_code}
              </p>
            </div>
            <span className="rounded-full bg-brand-700 px-3 py-1 text-xs font-medium text-brand-100">
              {BOOKING_STATUS_LABELS[receipt?.status] ?? receipt?.status}
            </span>
          </div>

          <div className="divide-y divide-border">
            <div className="grid gap-4 px-6 py-5 sm:grid-cols-2">
              <div>
                <p className="text-xs uppercase tracking-wider text-muted-foreground">
                  <CalendarDays className="mr-1 inline size-3.5" /> {t("confirm.stay")}
                </p>
                <p className="mt-1 text-sm font-semibold">
                  {formatDateRange(receipt.check_in, receipt.check_out)}
                </p>
                <p className="text-xs text-muted-foreground">
                  {nights} {t(nights === 1 ? "booking.night" : "booking.nights")} ·{" "}
                  {receipt.adults ?? 1} {t((receipt.adults ?? 1) === 1 ? "booking.adult" : "booking.adults")}
                  {receipt.children ? ` · ${receipt.children} child(ren)` : ""}
                </p>
              </div>
              <div>
                <p className="text-xs uppercase tracking-wider text-muted-foreground">
                  {t("confirm.amountDue")}
                </p>
                <p className="mt-1 text-xl font-bold text-brand-900">
                  {formatCurrency(receipt.total_amount)}
                </p>
                {receipt.deposit_amount > 0 && depositPaid ? (
                  <p className="text-xs font-medium text-emerald-600">
                    {t("confirm.depositPaid", { deposit: formatCurrency(receipt.deposit_amount), balance: formatCurrency((receipt.total_amount ?? 0) - receipt.deposit_amount) })}
                  </p>
                ) : (
                  receipt.deposit_amount > 0 && (
                    <p className="text-xs text-muted-foreground">
                      {t("confirm.depositDue", { rate: receipt.deposit_rate, deposit: formatCurrency(receipt.deposit_amount) })}
                    </p>
                  )
                )}
              </div>
            </div>

            {receipt.rooms?.length > 0 && (
              <div className="px-6 py-5">
                <p className="text-xs uppercase tracking-wider text-muted-foreground">
                  <BedDouble className="mr-1 inline size-3.5" /> {t("confirm.yourRooms")}
                </p>
                <ul className="mt-2 space-y-1.5">
                  {receipt.rooms.map((room) => (
                    <li
                      key={room.id}
                      className="flex items-center gap-2 text-sm"
                    >
                      <span className="grid size-5 shrink-0 place-items-center rounded-full bg-brand-100 text-brand-800">
                        <BedDouble className="size-3" />
                      </span>
                      {t("booking.roomNumber")} {room.room_number} ·{" "}
                      {t("confirm.roomType", { name: room.room_type?.name ?? room.room_type_id })}
                    </li>
                  ))}
                </ul>
              </div>
            )}

            <div className="flex flex-col gap-3 px-6 py-5 text-sm text-muted-foreground sm:flex-row sm:items-center sm:justify-between">
              <p className="flex items-center gap-1.5">
                <MapPin className="size-4 text-brand-700" /> {HOTEL_ADDRESS}
              </p>
              <p className="flex items-center gap-1.5">
                <Phone className="size-4 text-brand-700" /> {HOTEL_PHONE}
              </p>
            </div>
          </div>
        </div>

        <div className="mt-8 flex flex-col items-center justify-center gap-3 sm:flex-row">
          <Button
            size="pill"
            variant="outline"
            onClick={handleDownloadInvoice}
            disabled={downloading}
          >
            {downloading ? (
              <Loader2 className="size-4 animate-spin" />
            ) : (
              <FileDown />
            )}
            {t("confirm.download")}
          </Button>
          <Button
            size="pill"
            variant="dark"
            onClick={() => navigate(ROUTES.home)}
          >
            <Home /> {t("confirm.backHome")}
          </Button>
          <Button size="pill" onClick={() => navigate(ROUTES.profileBookings)}>
            {t("confirm.myBookings")} <ArrowRight />
          </Button>
          <Link
            to={ROUTES.contact}
            className="text-sm font-medium text-brand-800 hover:underline"
          >
            {t("confirm.question")}
          </Link>
        </div>
      </div>
    </section>
  );
}