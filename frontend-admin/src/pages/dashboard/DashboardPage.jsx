import { useQuery } from "@tanstack/react-query";
import { useState } from "react";
import {
  BedDouble,
  CalendarCheck,
  Users,
  Banknote,
  TrendingUp,
  ArrowUpRight,
  CircleDot,
} from "lucide-react";
import { fetchDashboard } from "@/services/api/dashboard";
import StatCard from "@/components/StatCard";
import PageHeader from "@/components/PageHeader";
import StatusBadge from "@/components/StatusBadge";
import { LoadingState, ErrorState, EmptyState } from "@/components/States";
import { formatCurrency, formatDateTime, formatRelative, initialsOf } from "@/lib/format";

const PERIODS = [
  { key: "today", label: "Today" },
  { key: "this_week", label: "This week" },
  { key: "this_month", label: "This month" },
  { key: "this_year", label: "This year" },
];

function BarChart({ data, accent = "#7FA35C" }) {
  const values = (data ?? []).map((d) => Number(d.revenue) || 0);
  const max = Math.max(...values, 1);

  return (
    <div>
      <div className="flex h-44 items-end gap-1.5 sm:h-52">
        {(data ?? []).map((d, i) => {
          const h = Math.max(4, (Number(d.revenue) || 0) / max * 100);
          return (
            <div
              key={d.label + i}
              className="group relative flex flex-1 flex-col items-center justify-end self-stretch"
            >
              <div className="pointer-events-none absolute -top-9 hidden whitespace-nowrap rounded-lg bg-[#16261F] px-2 py-1 text-[11px] text-[#F2F5EC] shadow-lg group-hover:block">
                {d.label} · {formatCurrency(d.revenue)}
              </div>
              <div
                className="w-full rounded-t-md transition-all duration-300 group-hover:opacity-80"
                style={{ height: `${h}%`, backgroundColor: accent }}
              />
            </div>
          );
        })}
      </div>
      <div className="mt-2 flex gap-1.5">
        {(data ?? []).map((d, i) => (
          <span key={d.label + i} className="flex-1 truncate text-center text-[10px] text-[#A8B39F]">
            {d.label}
          </span>
        ))}
      </div>
    </div>
  );
}

function BookingOverview({ data }) {
  const total =
    (data?.confirmed ?? 0) +
    (data?.in_house ?? 0) +
    (data?.pending ?? 0) +
    (data?.cancelled ?? 0) +
    (data?.completed ?? 0) || 1;

  const segments = [
    { key: "completed", label: "Completed", color: "#16261F", value: Number(data?.completed) || 0 },
    { key: "confirmed", label: "Confirmed", color: "#7FA35C", value: Number(data?.confirmed) || 0 },
    { key: "in_house", label: "In-house", color: "#3B6FB6", value: Number(data?.in_house) || 0 },
    { key: "pending", label: "Pending", color: "#D9A441", value: Number(data?.pending) || 0 },
    { key: "cancelled", label: "Cancelled", color: "#C25B50", value: Number(data?.cancelled) || 0 },
  ];

  return (
    <div className="flex flex-col gap-5">
      <div className="flex h-3 overflow-hidden rounded-full bg-[#EEF1E9]">
        {segments.map((s) => (
          <div
            key={s.key}
            className="h-full transition-all duration-500"
            style={{ width: `${(s.value / total) * 100}%`, backgroundColor: s.color }}
          />
        ))}
      </div>
      <div className="grid grid-cols-2 gap-3">
        {segments.map((s) => (
          <div key={s.key} className="flex items-center gap-2.5">
            <span className="h-2.5 w-2.5 rounded-full" style={{ backgroundColor: s.color }} />
            <div className="min-w-0">
              <p className="text-sm text-[#1E2B22]">{s.value}</p>
              <p className="text-xs text-[#7A8677]">{s.label}</p>
            </div>
          </div>
        ))}
      </div>
    </div>
  );
}

function RecentBookings({ bookings }) {
  return (
    <div className="divide-y divide-[#EEF1E9]">
      {bookings.map((b) => (
        <div key={b.id} className="flex items-center gap-4 py-3.5 first:pt-1 last:pb-1">
          <span className="flex h-10 w-10 shrink-0 items-center justify-center rounded-lg bg-[#7FA35C]/12 text-[#4F7A3B]">
            {initialsOf(b.guest?.full_name)}
          </span>
          <div className="min-w-0 flex-1">
            <p className="truncate text-sm font-medium text-[#1E2B22]">
              {b.guest?.full_name}
            </p>
            <p className="text-xs text-[#7A8677]">
              {b.booking_code} · {formatRelative(b.created_at)}
            </p>
          </div>
          <div className="text-right">
            <p className="text-sm font-semibold text-[#1E2B22]">
              {formatCurrency(b.total_amount)}
            </p>
            <StatusBadge status={b.status} className="mt-1 !px-2 !py-0.5" />
          </div>
        </div>
      ))}
    </div>
  );
}

function ActivityTimeline({ activities }) {
  if (!activities?.length) {
    return <EmptyState title="No recent activity" description="Activity from bookings will show up here." />;
  }
  return (
    <div className="relative space-y-5 before:absolute before:left-[11px] before:top-2 before:h-[calc(100%-16px)] before:w-px before:bg-[#DCE3D5]">
      {activities.map((a) => (
        <div key={a.id} className="relative flex gap-3.5 pl-0">
          <span className="relative z-10 mt-0.5 flex h-6 w-6 shrink-0 items-center justify-center rounded-full bg-white ring-1 ring-[#DCE3D5]">
            <CircleDot className="h-3 w-3 text-[#7FA35C]" strokeWidth={2} />
          </span>
          <div className="min-w-0">
            <p className="text-sm text-[#1E2B22]">
              <span className="font-medium">{a.changedBy?.name || "System"}</span>{" "}
              <span className="text-[#7A8677]">
                marked{" "}
                <span className="font-medium text-[#1E2B22]">
                  {a.booking?.booking_code}
                </span>{" "}
                as
              </span>{" "}
              <StatusBadge status={a.status} className="align-middle !px-2 !py-0.5" />
            </p>
            {a.note && <p className="mt-0.5 text-xs text-[#7A8677]">{a.note}</p>}
            <p className="mt-0.5 text-xs text-[#A8B39F]">{formatDateTime(a.created_at)}</p>
          </div>
        </div>
      ))}
    </div>
  );
}

export default function DashboardPage() {
  const [period, setPeriod] = useState("this_week");
  const { data, isLoading, isError, refetch } = useQuery({
    queryKey: ["dashboard-summary"],
    queryFn: fetchDashboard,
    refetchInterval: 8000,
  });

  if (isLoading) return <LoadingState label="Loading dashboard…" />;
  if (isError) return <ErrorState onRetry={refetch} />;

  const summary = data ?? {};
  const stats = summary.stats ?? {};
  const chartData = summary.revenue_chart?.[period] ?? [];
  const rooms = summary.room_status ?? {};

  const roomStatusRow = [
    { key: "available", label: "Available", color: "#7FA35C" },
    { key: "occupied", label: "Occupied", color: "#1E2B22" },
    { key: "maintenance", label: "Maintenance", color: "#D9A441" },
    { key: "cleaning", label: "Cleaning", color: "#B5C9A4" },
    { key: "out_of_service", label: "Out of service", color: "#C25B50" },
  ];

  return (
    <div>
      <PageHeader
        title="Dashboard"
        description="A live snapshot of your property's performance."
      />

      {/* Stat cards */}
      <div className="grid grid-cols-2 gap-3 sm:gap-4 xl:grid-cols-5">
        <StatCard label="Total rooms" value={stats.total_rooms ?? 0} icon={BedDouble} accent="dark" />
        <StatCard label="Today's bookings" value={stats.today_bookings ?? 0} icon={CalendarCheck} accent="leaf" />
        <StatCard label="Total guests" value={stats.total_guests ?? 0} icon={Users} accent="sand" />
        <StatCard label="Today's revenue" value={formatCurrency(stats.today_revenue ?? 0)} icon={TrendingUp} accent="gold" />
        <StatCard label="Total revenue" value={formatCurrency(stats.total_revenue ?? 0)} icon={Banknote} accent="leaf" />
      </div>

      <div className="mt-4 grid grid-cols-1 gap-4 lg:grid-cols-3">
        {/* Revenue chart */}
        <div className="rounded-xl border border-[#DCE3D5] bg-white p-5 lg:col-span-2">
          <div className="flex flex-wrap items-center justify-between gap-3">
            <div>
              <h2 className="text-base font-medium text-[#1E2B22]" style={{ fontFamily: "'Fraunces', serif" }}>
                Revenue
              </h2>
              <p className="text-xs text-[#7A8677]">
                {PERIODS.find((p) => p.key === period)?.label} · paid payments only
              </p>
            </div>
            <div className="flex rounded-lg border border-[#DCE3D5] bg-[#F8F9F4] p-0.5">
              {PERIODS.map((p) => (
                <button
                  key={p.key}
                  type="button"
                  onClick={() => setPeriod(p.key)}
                  className={`rounded-md px-2.5 py-1.5 text-xs font-medium transition-colors ${
                    period === p.key
                      ? "bg-[#16261F] text-[#F2F5EC] shadow-sm"
                      : "text-[#5E6B5A] hover:text-[#1E2B22]"
                  }`}
                >
                  {p.label}
                </button>
              ))}
            </div>
          </div>
          {chartData.length ? (
            <div className="mt-6">
              <BarChart data={chartData} />
            </div>
          ) : (
            <EmptyState title="No revenue yet" className="mt-6" />
          )}
        </div>

        {/* Booking overview */}
        <div className="rounded-xl border border-[#DCE3D5] bg-white p-5">
          <h2 className="text-base font-medium text-[#1E2B22]" style={{ fontFamily: "'Fraunces', serif" }}>
            Booking overview
          </h2>
          <p className="text-xs text-[#7A8677]">Breakdown by status</p>
          <div className="mt-6">
            <BookingOverview data={summary.booking_overview ?? {}} />
          </div>
          <div className="mt-6 border-t border-[#EEF1E9] pt-4">
            <p className="text-xs font-medium uppercase tracking-wider text-[#7A8677]">
              Room status
            </p>
            <div className="mt-3 space-y-2.5">
              {roomStatusRow.map((r) => (
                <div key={r.key} className="flex items-center justify-between">
                  <div className="flex items-center gap-2">
                    <span className="h-2.5 w-2.5 rounded-full" style={{ backgroundColor: r.color }} />
                    <span className="text-sm text-[#5E6B5A]">{r.label}</span>
                  </div>
                  <span className="text-sm font-semibold text-[#1E2B22]">
                    {rooms[r.key] ?? 0}
                  </span>
                </div>
              ))}
            </div>
          </div>
        </div>
      </div>

      <div className="mt-4 grid grid-cols-1 gap-4 lg:grid-cols-3">
        {/* Recent bookings */}
        <div className="rounded-xl border border-[#DCE3D5] bg-white p-5 lg:col-span-2">
          <div className="mb-2 flex items-center justify-between">
            <h2 className="text-base font-medium text-[#1E2B22]" style={{ fontFamily: "'Fraunces', serif" }}>
              Recent bookings
            </h2>
            <ArrowUpRight className="h-4 w-4 text-[#A8B39F]" strokeWidth={1.5} />
          </div>
          {(summary.recent_bookings ?? []).length ? (
            <RecentBookings bookings={summary.recent_bookings} />
          ) : (
            <EmptyState title="No bookings yet" />
          )}
        </div>

        {/* Activity */}
        <div className="rounded-xl border border-[#DCE3D5] bg-white p-5">
          <h2 className="mb-4 text-base font-medium text-[#1E2B22]" style={{ fontFamily: "'Fraunces', serif" }}>
            Recent activity
          </h2>
          <ActivityTimeline activities={summary.recent_activities} />
        </div>
      </div>
    </div>
  );
}