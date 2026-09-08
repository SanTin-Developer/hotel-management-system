import { useRef, useState } from "react";
import { Link, Navigate, useLocation, useNavigate } from "react-router-dom";
import { QRCodeSVG } from "qrcode.react";
import {
  Check,
  Loader2,
  MapPin,
  FileDown,
  ShieldCheck,
  CreditCard,
  Smartphone,
} from "lucide-react";
import { Button } from "@/components/ui/button";
import { Input } from "@/components/ui/input";
import { Label } from "@/components/ui/label";
import {
  Dialog,
  DialogContent,
  DialogDescription,
  DialogFooter,
  DialogHeader,
  DialogTitle,
} from "@/components/ui/dialog";
import { formatCurrency } from "@/utils/formatCurrency";
import { formatDateRange } from "@/utils/formatDate";
import { useI18n } from "@/i18n";
import { extractApiMessage } from "@/utils/validation";
import { downloadInvoice } from "@/utils/invoice";
import { Invoice } from "@/components/booking/Invoice";
import { payBookingDeposit } from "@/services/api/bookings";
import { ROUTES, HOTEL_PHONE, HOTEL_ADDRESS } from "@/constants/routes";
import { BOOKING_STATUS_LABELS } from "@/constants/booking";
import { toast } from "sonner";

const METHODS = [
  {
    id: "card",
    image: "https://cdn-icons-png.flaticon.com/512/11078/11078490.png",
    titleKey: "payment.depositCard",
    noteKey: "payment.depositCardNote",
  },
  {
    id: "aba",
    image:
      "https://cdn.brandfetch.io/domain/ababank.com/fallback/lettermark/theme/dark/h/400/w/400/icon?c=1bfwsmEH20zzEfSNTed",
    titleKey: "payment.depositAba",
    noteKey: "payment.depositAbaNote",
  },
  {
    id: "wing",
    image:
      "https://i.pinimg.com/564x/9f/a7/c3/9fa7c33a0cd4d96bfb30b7cf5fd6d3ab.jpg",
    titleKey: "payment.depositWing",
    noteKey: "payment.depositWingNote",
  },
  {
    id: "acleda",
    image:
      "https://encrypted-tbn0.gstatic.com/images?q=tbn:ANd9GcTM5-yV2sYi1pMjPCySrTuZFoYpYRgpz88CtmtLgzcqSg&s=10",
    titleKey: "payment.depositAcleda",
    noteKey: "payment.depositAcledaNote",
  },
];

function formatCardNumber(value) {
  return value
    .replace(/\D/g, "")
    .slice(0, 16)
    .replace(/(.{4})/g, "$1 ")
    .trim();
}

function formatExpiry(value) {
  const digits = value.replace(/\D/g, "").slice(0, 4);
  if (digits.length <= 2) return digits;
  return `${digits.slice(0, 2)}/${digits.slice(2)}`;
}

function demoReference() {
  const stamp = Date.now().toString(36).toUpperCase().padStart(6, "0");
  const rand = Math.random().toString(36).slice(2, 7).toUpperCase();
  return `DEMO-${stamp}-${rand}`;
}

export function PaymentPage() {
  const { t } = useI18n();
  const navigate = useNavigate();
  const location = useLocation();
  const booking = location.state?.booking;
  const invoiceRef = useRef(null);
  const [method, setMethod] = useState(METHODS[0].id);
  const [dialogOpen, setDialogOpen] = useState(false);
  const [transactionId, setTransactionId] = useState("");
  const [cardNumber, setCardNumber] = useState("");
  const [cardHolder, setCardHolder] = useState("");
  const [cardExpiry, setCardExpiry] = useState("");
  const [cardCvc, setCardCvc] = useState("");
  const [transactionError, setTransactionError] = useState(null);
  const [submitting, setSubmitting] = useState(false);
  const [downloading, setDownloading] = useState(false);

  if (!booking) return <Navigate to={ROUTES.home} replace />;

  const selectedMethod = METHODS.find((m) => m.id === method);
  const depositRate = booking.deposit_rate ?? 20;
  const depositAmount = Number(
    booking.deposit_amount ?? (booking.total_amount ?? 0) * 0.2,
  );
  const balanceDue =
    Number(booking.total_amount ?? 0) - Number(depositAmount || 0);
  const isCard = method === "card";

  const nights = booking.booking_items?.length
    ? Math.max(...booking.booking_items.map((item) => item.nights ?? 1))
    : 1;

  const validateCard = () => {
    const numberDigits = cardNumber.replace(/\s/g, "");
    return (
      /^\d{15,16}$/.test(numberDigits) &&
      cardHolder.trim().length > 1 &&
      /^(0[1-9]|1[0-2])\/\d{2}$/.test(cardExpiry) &&
      /^\d{3,4}$/.test(cardCvc)
    );
  };

  const payDeposit = async () => {
    let reference;

    if (isCard) {
      if (!validateCard()) {
        setTransactionError(t("payment.cardInvalid"));
        return;
      }
      reference = demoReference();
    } else {
      reference = transactionId.trim();
      if (!reference) {
        setTransactionError(t("payment.txnRequired"));
        return;
      }
    }

    setSubmitting(true);
    setTransactionError(null);
    try {
      await payBookingDeposit(booking.id, method, reference);
      setDialogOpen(false);
      navigate(`${ROUTES.bookingConfirmation}?code=${booking.booking_code}`, {
        state: { booking, depositPaid: true },
      });
    } catch (err) {
      setTransactionError(extractApiMessage(err) ?? t("payment.txnFailed"));
      setSubmitting(false);
    }
  };

  const openPaymentDialog = () => {
    setTransactionId("");
    setCardNumber("");
    setCardHolder("");
    setCardExpiry("");
    setCardCvc("");
    setTransactionError(null);
    setDialogOpen(true);
  };

  const handleDownloadInvoice = async () => {
    setDownloading(true);
    try {
      await downloadInvoice(
        invoiceRef.current,
        `invoice-${booking.booking_code}.pdf`,
      );
    } catch {
      toast.error(t("confirm.invoiceError"));
    } finally {
      setDownloading(false);
    }
  };

  const qrValue = `KRPAY|${booking.booking_code}|${depositAmount.toFixed(2)}|${method.toUpperCase()}`;

  return (
    <section className="min-h-[70vh] bg-cream py-14">
      <div ref={invoiceRef}>
        <Invoice booking={booking} />
      </div>
      <div className="mx-auto max-w-3xl px-6">
        <div className="overflow-hidden rounded-2xl border border-border bg-white">
          <div className="border-b border-border bg-brand-900 px-6 py-5 text-white">
            <p className="text-xs font-semibold uppercase tracking-wider text-brand-200">
              {t("payment.details")}
            </p>
            <div className="mt-1 flex flex-wrap items-center justify-between gap-2">
              <h1 className="text-xl font-semibold sm:text-2xl">
                {booking.booking_code}
              </h1>
              <span className="rounded-full bg-brand-700 px-3 py-1 text-xs font-medium text-brand-100">
                {t(BOOKING_STATUS_LABELS[booking.status] ?? booking.status)}
              </span>
            </div>
          </div>

          <div className="divide-y divide-border">
            <div className="grid gap-4 px-6 py-5 text-sm sm:grid-cols-3">
              <div>
                <p className="text-xs uppercase tracking-wider text-muted-foreground">
                  {t("payment.dates")}
                </p>
                <p className="mt-1 font-medium">
                  {formatDateRange(booking.check_in, booking.check_out)}
                </p>
                <p className="text-xs text-muted-foreground">
                  {nights}{" "}
                  {t(nights === 1 ? "booking.night" : "booking.nights")}
                </p>
              </div>
              <div>
                <p className="text-xs uppercase tracking-wider text-muted-foreground">
                  {t("payment.rooms")}
                </p>
                <p className="mt-1 font-medium">
                  {booking.booking_items?.length ?? 1}{" "}
                  {t(
                    (booking.booking_items?.length ?? 1) === 1
                      ? "payment.room"
                      : "payment.roomsPlural",
                  )}
                </p>
              </div>
              <div>
                <p className="text-xs uppercase tracking-wider text-muted-foreground">
                  {t("payment.totalStay")}
                </p>
                <p className="mt-1 text-lg font-bold text-brand-900">
                  {formatCurrency(booking.total_amount)}
                </p>
              </div>
            </div>

            <div className="px-6 py-6">
              <div className="flex items-start gap-2 rounded-xl border border-amber-200 bg-amber-50 px-4 py-3">
                <ShieldCheck className="mt-0.5 size-4 shrink-0 text-amber-700" />
                <p className="text-xs leading-relaxed text-amber-800">
                  {t("payment.needsDeposit", {
                    rate: depositRate,
                    deposit: formatCurrency(depositAmount),
                    balance: formatCurrency(balanceDue),
                  })}
                </p>
              </div>

              <h2 className="mt-6 font-semibold">
                {t("payment.chooseMethod")}
              </h2>
              <p className="mt-1 text-sm text-muted-foreground">
                {t("payment.chooseMethodDesc")}
              </p>
              <div className="mt-4 grid gap-3 sm:grid-cols-2 lg:grid-cols-4">
                {METHODS.map((m) => {
                  const active = method === m.id;

                  return (
                    <button
                      key={m.id}
                      type="button"
                      onClick={() => setMethod(m.id)}
                      className={`relative rounded-2xl border-2 p-4 text-left transition-all ${
                        active
                          ? "border-brand-900 bg-brand-50"
                          : "border-border bg-white hover:border-brand-300"
                      }`}
                    >
                      {active && (
                        <span className="absolute right-3 top-3 grid size-5 place-items-center rounded-full bg-brand-900 text-white">
                          <Check className="size-3" />
                        </span>
                      )}

                      <img
                        src={m.image}
                        alt={t(m.titleKey)}
                        loading="lazy"
                        className="h-8 w-12 object-contain"
                      />

                      <p className="mt-2.5 font-semibold">{t(m.titleKey)}</p>

                      <p className="mt-0.5 text-xs text-muted-foreground">
                        {t(m.noteKey)}
                      </p>
                    </button>
                  );
                })}
              </div>

              <div className="mt-5 rounded-xl border border-brand-200 bg-brand-50 p-5">
                <div className="space-y-1.5 text-sm text-brand-800">
                  <div className="flex items-center justify-between">
                    <span>{t("payment.totalStay")}</span>
                    <span className="font-semibold">
                      {formatCurrency(booking.total_amount)}
                    </span>
                  </div>
                  <div className="flex items-center justify-between">
                    <span>{t("payment.depositNow", { rate: depositRate })}</span>
                    <span className="font-semibold">
                      {formatCurrency(depositAmount)}
                    </span>
                  </div>
                  <div className="flex items-center justify-between border-t border-brand-200 pt-1.5">
                    <span className="font-semibold">
                      {t("payment.balanceHotel")}
                    </span>
                    <span className="font-semibold">
                      {formatCurrency(balanceDue)}
                    </span>
                  </div>
                </div>
                <p className="mt-3 text-xs text-brand-800">
                  {isCard
                    ? t("payment.cardCharged")
                    : t("payment.paymentCollected")}{" "}
                  {t("payment.balanceLater")}
                </p>
              </div>
            </div>
          </div>

          <div className="flex flex-col gap-3 border-t border-border px-6 py-5 sm:flex-row sm:items-center sm:justify-between">
            <p className="flex items-center gap-1.5 text-xs text-muted-foreground">
              <MapPin className="size-3.5" /> {HOTEL_ADDRESS}
            </p>
            <div className="flex gap-3">
              <Button
                variant="outline"
                size="pill"
                onClick={handleDownloadInvoice}
                disabled={downloading}
              >
                {downloading ? (
                  <Loader2 className="size-4 animate-spin" />
                ) : (
                  <FileDown />
                )}
                {t("payment.invoice")}
              </Button>
              <Button
                size="pill-lg"
                onClick={openPaymentDialog}
                disabled={submitting}
              >
                {submitting && <Loader2 className="size-4 animate-spin" />}
                {t("payment.payWith", {
                  amount: formatCurrency(depositAmount),
                  method: t(selectedMethod?.titleKey),
                })}
              </Button>
            </div>
          </div>
        </div>

        <p className="mt-6 text-center text-sm text-muted-foreground">
          {t("payment.questions")}{" "}
          <Link to={ROUTES.contact} className="text-brand-800 hover:underline">
            {t("payment.contactUs")}
          </Link>{" "}
          {t("payment.orCall", { phone: HOTEL_PHONE })}
        </p>
      </div>

      <Dialog open={dialogOpen} onOpenChange={setDialogOpen}>
        <DialogContent className="sm:max-w-md">
          <DialogHeader>
            <DialogTitle>
              {t("payment.payWith", {
                amount: formatCurrency(depositAmount),
                method: t(selectedMethod?.titleKey),
              })}
            </DialogTitle>
            <DialogDescription>
              {isCard ? t("payment.cardCharged") : t("payment.dialogDesc")}
            </DialogDescription>
          </DialogHeader>

          {isCard ? (
            <div className="grid gap-4">
              <div className="flex items-center gap-3 rounded-xl border border-brand-200 bg-brand-50 px-4 py-3">
                <CreditCard className="size-5 shrink-0 text-brand-800" />
                <p className="text-xs text-brand-800">
                  {t("payment.cardDemoNote")}
                </p>
              </div>

              <div className="space-y-1.5">
                <Label htmlFor="card-number">{t("payment.cardNumber")}</Label>
                <Input
                  id="card-number"
                  className="h-11"
                  inputMode="numeric"
                  autoComplete="cc-number"
                  placeholder={t("payment.cardNumberPlaceholder")}
                  value={cardNumber}
                  onChange={(event) => {
                    setCardNumber(formatCardNumber(event.target.value));
                    setTransactionError(null);
                  }}
                />
              </div>

              <div className="space-y-1.5">
                <Label htmlFor="card-holder">{t("payment.cardHolder")}</Label>
                <Input
                  id="card-holder"
                  className="h-11"
                  autoComplete="cc-name"
                  value={cardHolder}
                  onChange={(event) => {
                    setCardHolder(event.target.value);
                    setTransactionError(null);
                  }}
                />
              </div>

              <div className="grid grid-cols-2 gap-3">
                <div className="space-y-1.5">
                  <Label htmlFor="card-expiry">{t("payment.cardExpiry")}</Label>
                  <Input
                    id="card-expiry"
                    className="h-11"
                    inputMode="numeric"
                    autoComplete="cc-exp"
                    placeholder={t("payment.cardExpiryPlaceholder")}
                    value={cardExpiry}
                    onChange={(event) => {
                      setCardExpiry(formatExpiry(event.target.value));
                      setTransactionError(null);
                    }}
                  />
                </div>
                <div className="space-y-1.5">
                  <Label htmlFor="card-cvc">{t("payment.cardCvc")}</Label>
                  <Input
                    id="card-cvc"
                    className="h-11"
                    inputMode="numeric"
                    autoComplete="cc-csc"
                    placeholder={t("payment.cardCvcPlaceholder")}
                    value={cardCvc}
                    onChange={(event) => {
                      setCardCvc(
                        event.target.value.replace(/\D/g, "").slice(0, 4),
                      );
                      setTransactionError(null);
                    }}
                  />
                </div>
              </div>

              {transactionError && (
                <div className="rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">
                  {transactionError}
                </div>
              )}
            </div>
          ) : (
            <div className="grid gap-4">
              <div className="flex items-center gap-3 rounded-xl border border-brand-200 bg-brand-50 px-4 py-3">
                <Smartphone className="size-5 shrink-0 text-brand-800" />
                <p className="text-xs text-brand-800">
                  {t("payment.scanHint", { method: t(selectedMethod?.titleKey) })}
                </p>
              </div>

              <div className="flex flex-col items-center gap-2 rounded-2xl border border-border bg-white p-5">
                <div className="rounded-xl border border-brand-100 p-3">
                  <QRCodeSVG value={qrValue} size={190} level="M" />
                </div>
                <p className="text-sm font-semibold">{t("payment.scanTitle")}</p>
                <p className="text-xs text-muted-foreground">
                  {t("payment.qrAmount", {
                    amount: formatCurrency(depositAmount),
                  })}{" "}
                  ·{" "}
                  {t("payment.qrRef", {
                    ref: `${booking.booking_code}-${method.toUpperCase()}`,
                  })}
                </p>
              </div>

              <div className="space-y-1.5">
                <Label htmlFor="txn-id">{t("payment.txnId")}</Label>
                <Input
                  id="txn-id"
                  className="h-11"
                  placeholder={t("payment.txnPlaceholder")}
                  value={transactionId}
                  onChange={(event) => {
                    setTransactionId(event.target.value);
                    setTransactionError(null);
                  }}
                />
                <p className="text-xs text-muted-foreground">
                  {t("payment.txnHint")}
                </p>
              </div>

              {transactionError && (
                <div className="rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">
                  {transactionError}
                </div>
              )}
            </div>
          )}

          <DialogFooter>
            <Button
              type="button"
              size="pill"
              onClick={payDeposit}
              disabled={submitting}
            >
              {submitting && <Loader2 className="size-4 animate-spin" />}
              {t("payment.confirmDeposit")}
            </Button>
          </DialogFooter>
        </DialogContent>
      </Dialog>
    </section>
  );
}