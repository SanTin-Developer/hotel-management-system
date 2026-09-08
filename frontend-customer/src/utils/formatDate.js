import { format, parseISO } from "date-fns";

export function formatDate(date, pattern = "MMM d, yyyy") {
  if (!date) return "";
  const parsed = typeof date === "string" ? parseISO(date) : date;
  if (Number.isNaN(parsed.getTime())) return "";
  return format(parsed, pattern);
}

export function formatDateShort(date) {
  return formatDate(date, "MMM d");
}

export function formatDateRange(checkIn, checkOut) {
  return `${formatDate(checkIn)} – ${formatDate(checkOut)}`;
}

export function toDateInputValue(date) {
  const parsed = typeof date === "string" ? parseISO(date) : date;
  if (!parsed || Number.isNaN(parsed.getTime())) return "";
  return format(parsed, "yyyy-MM-dd");
}

export function todayISO() {
  return format(new Date(), "yyyy-MM-dd");
}

export function addDaysISO(date, days) {
  const parsed = typeof date === "string" ? parseISO(date) : date;
  const copy = new Date(parsed);
  copy.setDate(copy.getDate() + days);
  return format(copy, "yyyy-MM-dd");
}

export function diffInDays(from, to) {
  const start = typeof from === "string" ? parseISO(from) : from;
  const end = typeof to === "string" ? parseISO(to) : to;
  return Math.max(0, Math.round((end - start) / 86_400_000));
}