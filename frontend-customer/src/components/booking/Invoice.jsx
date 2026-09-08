import {
  HOTEL_NAME,
  HOTEL_ADDRESS,
  HOTEL_PHONE,
  HOTEL_EMAIL,
  HOTEL_LOGO_URL,
} from "@/constants/routes";
import { formatCurrency } from "@/utils/formatCurrency";
import { formatDateRange } from "@/utils/formatDate";
import { BOOKING_STATUS_LABELS } from "@/constants/booking";
import { useI18n } from "@/i18n";

export function Invoice({ booking, depositPaid = false }) {
  const { t } = useI18n();
  const nights = booking.booking_items?.length
    ? Math.max(...booking.booking_items.map((item) => item.nights ?? 1))
    : 1;

  const depositRate = booking.deposit_rate ?? 20;
  const depositAmount = booking.deposit_amount ?? (booking.total_amount ?? 0) * 0.2;
  const balanceDue = (booking.total_amount ?? 0) - depositAmount;
  const guest = booking.guest ?? {};

  const roomNumberFor = (roomId) =>
    booking.rooms?.find((room) => room.id === roomId)?.room_number ??
    `Room #${roomId}`;

  return (
    <div
      aria-hidden="true"
      style={{ position: "absolute", left: "-9999px", top: 0, width: "794px" }}
    >
      <div
        data-invoice-root=""
        style={{
          padding: "48px 52px",
          fontFamily: "Geist, Arial, sans-serif",
          color: "#1a202c",
          background: "#ffffff",
        }}
      >
        {/* Header */}
        <div
          style={{
            display: "flex",
            justifyContent: "space-between",
            alignItems: "flex-start",
            borderBottom: "3px solid #12343b",
            paddingBottom: "20px",
          }}
        >
          <div style={{ display: "flex", alignItems: "center", gap: "14px" }}>
            <img
              src={HOTEL_LOGO_URL}
              alt=""
              crossOrigin="anonymous"
              style={{ width: "58px", height: "58px", objectFit: "contain" }}
            />
            <div>
              <div style={{ fontSize: "22px", fontWeight: 700, color: "#12343b" }}>
                {HOTEL_NAME}
              </div>
              <div style={{ fontSize: "11px", color: "#5c6b70", marginTop: "2px" }}>
                {HOTEL_ADDRESS}
              </div>
            </div>
          </div>
          <div style={{ textAlign: "right" }}>
            <div style={{ fontSize: "11px", color: "#5c6b70" }}>
              {HOTEL_PHONE}
              <br />
              {HOTEL_EMAIL}
            </div>
          </div>
        </div>

        {/* Title */}
        <div
          style={{
            display: "flex",
            justifyContent: "space-between",
            alignItems: "center",
            margin: "24px 0 4px",
          }}
        >
          <div>
            <div style={{ fontSize: "28px", fontWeight: 700, color: "#12343b" }}>
              {t("payment.invoice")}
            </div>
            <div style={{ fontSize: "11px", color: "#5c6b70", marginTop: "2px" }}>
              Issued{" "}
              {booking.created_at
                ? new Date(booking.created_at).toLocaleDateString()
                : ""}
            </div>
          </div>
          <div style={{ textAlign: "right" }}>
            <div style={{ fontSize: "12px", color: "#5c6b70" }}>{t("confirm.reference")}</div>
            <div
              style={{
                fontSize: "16px",
                fontWeight: 700,
                letterSpacing: "1px",
                color: "#12343b",
              }}
            >
              {booking.booking_code}
            </div>
          </div>
        </div>

        {/* Status chip */}
        <div
          style={{
            display: "inline-block",
            background: "#e8f0ef",
            color: "#12343b",
            fontSize: "11px",
            fontWeight: 600,
            padding: "4px 10px",
            borderRadius: "999px",
          }}
        >
          {BOOKING_STATUS_LABELS[booking.status] ?? booking.status}
        </div>

        {/* Bill to + stay */}
        <div
          style={{
            display: "flex",
            justifyContent: "space-between",
            gap: "24px",
            marginTop: "18px",
          }}
        >
          <div style={{ width: "50%" }}>
            <div style={{ fontSize: "11px", fontWeight: 600, textTransform: "uppercase", color: "#5c6b70" }}>
              Billed to
            </div>
            <div style={{ fontSize: "14px", fontWeight: 600, color: "#1a202c", marginTop: "6px" }}>
              {guest.full_name ?? t("profile.guest")}
            </div>
            <div style={{ fontSize: "12px", color: "#4a5560", marginTop: "2px" }}>
              {guest.phone ?? ""}
              {guest.phone && guest.email ? " · " : ""}
              {guest.email ?? ""}
            </div>
          </div>
          <div style={{ width: "50%" }}>
            <div style={{ fontSize: "11px", fontWeight: 600, textTransform: "uppercase", color: "#5c6b70" }}>
              {t("booking.stepDates")}
            </div>
            <div style={{ fontSize: "14px", fontWeight: 600, color: "#1a202c", marginTop: "6px" }}>
              {formatDateRange(booking.check_in, booking.check_out)}
            </div>
            <div style={{ fontSize: "12px", color: "#4a5560", marginTop: "2px" }}>
              {nights} {t(nights === 1 ? "booking.night" : "booking.nights")} ·{" "}
              {booking.adults ?? 1} {t((booking.adults ?? 1) === 1 ? "booking.adult" : "booking.adults")}
              {booking.children ? ` · ${booking.children} child(ren)` : ""}
            </div>
          </div>
        </div>

        {/* Rooms table */}
        <table style={{ width: "100%", borderCollapse: "collapse", marginTop: "22px", fontSize: "12px" }}>
          <thead>
            <tr style={{ background: "#12343b", color: "#ffffff" }}>
              <th style={{ padding: "8px 10px", textAlign: "left", fontWeight: 600 }}>Room</th>
              <th style={{ padding: "8px 10px", textAlign: "right", fontWeight: 600 }}>Rate / night</th>
              <th style={{ padding: "8px 10px", textAlign: "center", fontWeight: 600 }}>Nights</th>
              <th style={{ padding: "8px 10px", textAlign: "right", fontWeight: 600 }}>Subtotal</th>
            </tr>
          </thead>
          <tbody>
            {(booking.booking_items ?? []).map((item) => (
              <tr key={item.id} style={{ borderBottom: "1px solid #e3e8e8" }}>
                <td style={{ padding: "8px 10px", color: "#1a202c" }}>
                  {roomNumberFor(item.room_id)}
                </td>
                <td style={{ padding: "8px 10px", textAlign: "right", color: "#1a202c" }}>
                  {formatCurrency(item.price_per_night)}
                </td>
                <td style={{ padding: "8px 10px", textAlign: "center", color: "#1a202c" }}>
                  {item.nights}
                </td>
                <td style={{ padding: "8px 10px", textAlign: "right", color: "#1a202c", fontWeight: 600 }}>
                  {formatCurrency(item.subtotal)}
                </td>
              </tr>
            ))}
          </tbody>
        </table>

        {/* Totals */}
        <div
          style={{
            display: "flex",
            justifyContent: "flex-end",
            marginTop: "16px",
          }}
        >
          <div style={{ width: "45%", fontSize: "12px", color: "#1a202c" }}>
            <div style={{ display: "flex", justifyContent: "space-between", padding: "4px 0" }}>
              <span style={{ color: "#4a5560" }}>{t("payment.totalStay")}</span>
              <span style={{ fontWeight: 700 }}>{formatCurrency(booking.total_amount)}</span>
            </div>
            <div style={{ display: "flex", justifyContent: "space-between", padding: "4px 0" }}>
              <span style={{ color: "#4a5560" }}>
                {depositPaid ? "Deposit paid (online)" : `Deposit (${depositRate}%)`}
              </span>
              <span style={{ fontWeight: 600 }}>{formatCurrency(depositAmount)}</span>
            </div>
            <div
              style={{
                display: "flex",
                justifyContent: "space-between",
                padding: "8px 0 4px",
                borderTop: "2px solid #12343b",
              }}
            >
              <span style={{ fontWeight: 700, color: "#12343b" }}>
                {t("payment.balanceHotel")}
              </span>
              <span style={{ fontWeight: 700, color: "#12343b" }}>{formatCurrency(balanceDue)}</span>
            </div>
          </div>
        </div>

        {/* Footer */}
        <div
          style={{
            marginTop: "34px",
            borderTop: "1px solid #e3e8e8",
            paddingTop: "14px",
            textAlign: "center",
            fontSize: "11px",
            color: "#5c6b70",
          }}
        >
          {depositPaid
            ? "Deposit received. Please settle the remaining balance at the hotel on your check-in date."
            : "The deposit and balance are settled at the hotel on your check-in date (cash, card, or bank transfer)."}
          <br />
          Thank you for choosing {HOTEL_NAME}. We look forward to welcoming you.
        </div>
      </div>
    </div>
  );
}