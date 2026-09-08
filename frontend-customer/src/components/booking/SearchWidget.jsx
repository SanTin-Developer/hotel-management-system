import { useState } from "react";
import { useNavigate } from "react-router-dom";
import { format, addDays } from "date-fns";
import {
  Calendar as CalendarIcon,
  Users,
  Search,
  Minus,
  Plus,
  ChevronDown,
} from "lucide-react";
import { Calendar } from "@/components/ui/calendar";
import { Button } from "@/components/ui/button";
import { Popover } from "@/components/common/Popover";
import { useI18n } from "@/i18n";
import { ROUTES } from "@/constants/routes";
import { useBookingStore } from "@/store/bookingStore";
import { cn } from "@/lib/utils";

function toDate(iso) {
  return iso ? new Date(`${iso}T00:00:00`) : null;
}

function DateField({ label, value, minDate, onSelect, placeholder }) {
  return (
    <Popover
      align="start"
      contentClassName="w-[min(21rem,calc(100vw-2rem))] rounded-2xl border border-brand-100 bg-white p-3 shadow-xl ring-1 ring-black/5"
      trigger={
        <button type="button" className="flex w-full items-center justify-between gap-2 text-left">
          <span>
            <span className="block text-[0.65rem] font-semibold uppercase tracking-wider text-muted-foreground">
              {label}
            </span>
            <span className={cn("mt-0.5 block text-sm font-semibold", !value && "text-muted-foreground")}>
              {value ? format(value, "EEE, MMM d") : placeholder}
            </span>
          </span>
          <CalendarIcon className="size-4 shrink-0 text-brand-900" />
        </button>
      }
    >
      <div className="rounded-xl border bg-white">
        <Calendar
          mode="single"
          selected={value ?? undefined}
          onSelect={onSelect}
          numberOfMonths={1}
          disabled={{ before: minDate }}
          fromDate={minDate}
        />
      </div>
    </Popover>
  );
}

function StepperRow({ field, label, hint, value, min, max, onStep }) {
  const { t } = useI18n();
  return (
    <div className="flex items-center justify-between">
      <div>
        <p className="text-sm font-medium">{label}</p>
        <p className="text-xs text-muted-foreground">{hint}</p>
      </div>
      <div className="flex items-center gap-3">
        <button
          type="button"
          onClick={() => onStep(field, -1)}
          disabled={value <= min}
          className="grid size-8 place-items-center rounded-full border border-border transition-colors hover:bg-brand-50 disabled:opacity-40"
          aria-label={t("search.decrease", { field })}
        >
          <Minus className="size-4" />
        </button>
        <span className="w-5 text-center text-sm font-semibold">{value}</span>
        <button
          type="button"
          onClick={() => onStep(field, 1)}
          disabled={value >= max}
          className="grid size-8 place-items-center rounded-full border border-border transition-colors hover:bg-brand-50 disabled:opacity-40"
          aria-label={t("search.increase", { field })}
        >
          <Plus className="size-4" />
        </button>
      </div>
    </div>
  );
}

function GuestsField({ adults, children, onChange }) {
  const { t } = useI18n();
  const total = (adults ?? 0) + (children ?? 0);

  const step = (field, delta) => {
    const next = field === "adults" ? (adults ?? 2) + delta : (children ?? 0) + delta;
    if (field === "adults" && next < 1) return;
    if (field === "children" && next < 0) return;
    if (next > 20) return;
    const nextAdults = field === "adults" ? next : adults;
    const nextChildren = field === "children" ? next : children;
    onChange({ adults: nextAdults, children: nextChildren });
  };

  return (
    <Popover
      align="start"
      contentClassName="w-[min(19rem,calc(100vw-2rem))] rounded-2xl border border-brand-100 bg-white p-4 shadow-xl ring-1 ring-black/5"
      trigger={
        <button type="button" className="flex w-full items-center justify-between gap-2 text-left">
          <span>
            <span className="block text-[0.65rem] font-semibold uppercase tracking-wider text-muted-foreground">
              {t("search.occupancy")}
            </span>
            <span className="mt-0.5 flex items-center gap-1 text-sm font-semibold text-foreground">
              {total} {t("search.occupancy").toLowerCase()}
              <ChevronDown className="size-3.5 text-muted-foreground" />
            </span>
          </span>
          <Users className="size-4 shrink-0 text-brand-900" />
        </button>
      }
    >
      <div className="space-y-4">
        <StepperRow
          field="adults"
          label={t("search.adults")}
          hint="18+"
          value={adults}
          min={1}
          max={20}
          onStep={step}
        />
        <div className="border-t border-border" />
        <StepperRow
          field="children"
          label={t("search.children")}
          hint="0–17"
          value={children}
          min={0}
          max={20}
          onStep={step}
        />
      </div>
    </Popover>
  );
}

export function SearchWidget({ variant = "default", className, onSearch }) {
  const { t } = useI18n();
  const navigate = useNavigate();
  const { checkIn, checkOut, adults, children, setDates, setOccupancy } =
    useBookingStore();
  const [error, setError] = useState(null);

  const checkInDate = toDate(checkIn);
  const checkOutDate = toDate(checkOut);
  const today = new Date();
  const guests = (adults ?? 0) + (children ?? 0);

  const handleCheckInSelect = (date) => {
    setError(null);
    if (!date) return;
    const iso = format(date, "yyyy-MM-dd");
    if (checkOutDate && (date.getTime() >= checkOutDate.getTime())) {
      setDates(iso, format(addDays(date, 1), "yyyy-MM-dd"));
    } else {
      setDates(iso, checkOut);
    }
  };

  const handleCheckOutSelect = (date) => {
    setError(null);
    if (!date) return;
    setDates(checkIn, format(date, "yyyy-MM-dd"));
  };

  const handleSearch = () => {
    if (!checkIn || !checkOut) {
      setError(t("search.error"));
      return;
    }
    setError(null);
    if (onSearch) {
      onSearch();
      return;
    }
    navigate(`${ROUTES.rooms}?checkin=${checkIn}&checkout=${checkOut}&guests=${guests}`);
  };

  const fieldClassName =
    "rounded-xl border border-border bg-white p-3.5 transition-colors hover:border-brand-300 focus-within:border-brand-400";

  return (
    <div
      className={cn(
        variant === "floating"
          ? "rounded-2xl border border-brand-100 bg-white p-4 shadow-xl shadow-brand-950/10 ring-1 ring-black/5 sm:p-5"
          : "rounded-2xl border border-brand-100 bg-white p-3 shadow-xl shadow-brand-950/10 ring-1 ring-black/5 sm:p-4",
        className
      )}
    >
      <div
        className={cn(
          "grid gap-3",
          variant === "floating"
            ? "md:grid-cols-[1fr_1fr_0.9fr_auto]"
            : "lg:grid-cols-[1fr_1fr_0.9fr_auto]"
        )}
      >
        <div className={fieldClassName}>
          <DateField
            label={t("search.checkIn")}
            value={checkInDate}
            minDate={today}
            onSelect={handleCheckInSelect}
            placeholder={t("search.checkIn")}
          />
        </div>

        <div className={fieldClassName}>
          <DateField
            label={t("search.checkOut")}
            value={checkOutDate}
            minDate={checkInDate ?? today}
            onSelect={handleCheckOutSelect}
            placeholder={t("search.checkOut")}
          />
        </div>

        <div className={fieldClassName}>
          <GuestsField
            adults={adults}
            children={children}
            onChange={({ adults: nextAdults, children: nextChildren }) =>
              setOccupancy(nextAdults, nextChildren)
            }
          />
        </div>

        <Button
          type="button"
          variant="default"
          size={variant === "floating" ? "pill-lg" : "pill"}
          className="h-full min-h-12 w-full md:min-w-40"
          onClick={handleSearch}
        >
          <Search />
          {t("search.search")}
        </Button>
      </div>

      {error && (
        <p className="mt-3 flex items-center gap-1.5 text-sm font-medium text-red-600">
          {error}
        </p>
      )}
    </div>
  );
}