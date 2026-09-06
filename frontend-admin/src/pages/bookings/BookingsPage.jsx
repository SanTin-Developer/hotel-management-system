import { useState } from "react";
import { useQuery, useMutation, useQueryClient } from "@tanstack/react-query";
import {
  Plus,
  Check,
  X,
  PartyPopper,
  Eye,
  CalendarDays,
  User as UserIcon,
  BedDouble,
} from "lucide-react";
import { toast } from "sonner";

import { Sheet, SheetContent, SheetHeader, SheetTitle, SheetDescription } from "@/components/ui/sheet";

import PageHeader from "@/components/PageHeader";
import DataTable from "@/components/DataTable";
import SearchInput from "@/components/SearchInput";
import useUrlQuerySearch from "@/hooks/useUrlQuerySearch";
import StatusBadge from "@/components/StatusBadge";
import Pagination from "@/components/Pagination";
import ConfirmDialog from "@/components/ConfirmDialog";
import FormModal, { FormActions } from "@/components/FormModal";
import { LoadingState, ErrorState } from "@/components/States";
import { TextField, SelectField, DateField, TextAreaField } from "@/components/form/Inputs";
import {
  fetchBookings,
  createBooking,
  confirmBooking,
  cancelBooking,
  completeBooking,
  fetchBookingPayments,
  fetchAvailability,
} from "@/services/api/bookings";
import { fetchGuests } from "@/services/api/guests";
import { fetchCoupons } from "@/services/api/coupons";
import { getErrorMessage, formatCurrency, formatDate, formatDateTime, titleCase, initialsOf } from "@/lib/format";

const STATUSES = ["pending", "confirmed", "cancelled", "completed"];

const BOOKING_TYPES = {
  website: "Online booking",
  walk_in: "With receptionist",
  phone: "By phone",
  third_party: "Third party",
};

function bookingTypeLabel(source) {
  return BOOKING_TYPES[source] ?? titleCase(source ?? "website");
}

function BookingCreateForm({ guests, coupons, onSuccess }) {
  const queryClient = useQueryClient();
  const [form, setForm] = useState({
    guest_id: "",
    check_in: "",
    check_out: "",
    adults: "2",
    children: "0",
    room_ids: [],
    coupon_id: "",
    special_request: "",
  });
  const [errors, setErrors] = useState({});

  const availabilityQuery = useQuery({
    queryKey: ["availability", form.check_in, form.check_out],
    queryFn: () =>
      fetchAvailability({ check_in: form.check_in, check_out: form.check_out }),
    enabled: Boolean(form.check_in && form.check_out),
    staleTime: 60_000,
  });

  const mutation = useMutation({
    mutationFn: createBooking,
    onSuccess: () => {
      toast.success("Booking created.");
      queryClient.invalidateQueries({ queryKey: ["bookings"] });
      queryClient.invalidateQueries({ queryKey: ["dashboard-summary"] });
      onSuccess();
    },
    onError: (error) => toast.error(getErrorMessage(error, "Could not create booking.")),
  });

  function set(field, value) {
    setForm((f) => ({ ...f, [field]: value }));
    setErrors((e) => ({ ...e, [field]: undefined, room_ids: undefined }));
  }

  function toggleRoom(id) {
    setForm((f) => ({
      ...f,
      room_ids: f.room_ids.includes(id)
        ? f.room_ids.filter((r) => r !== id)
        : [...f.room_ids, id],
    }));
    setErrors((e) => ({ ...e, room_ids: undefined }));
  }

  function handleSubmit(e) {
    e.preventDefault();
    const errs = {};
    if (!form.guest_id) errs.guest_id = "Please select a guest.";
    if (!form.check_in) errs.check_in = "Check-in is required.";
    if (!form.check_out) errs.check_out = "Check-out is required.";
    if (form.check_in && form.check_out && form.check_out <= form.check_in) {
      errs.check_out = "Check-out must be after check-in.";
    }
    if (!form.room_ids.length) errs.room_ids = "Select at least one room.";
    setErrors(errs);
    if (Object.keys(errs).length) return;
    mutation.mutate({
      guest_id: Number(form.guest_id),
      check_in: form.check_in,
      check_out: form.check_out,
      adults: Number(form.adults),
      children: Number(form.children || 0),
      room_ids: form.room_ids,
      coupon_id: form.coupon_id ? Number(form.coupon_id) : undefined,
      booking_source: "walk_in",
      special_request: form.special_request || undefined,
    });
  }

  const availableRooms = availabilityQuery.data?.data ?? availabilityQuery.data?.rooms ?? [];

  return (
    <form onSubmit={handleSubmit}>
      <div className="grid grid-cols-1 gap-4 sm:grid-cols-2">
        <SelectField
          label="Guest"
          name="guest_id"
          value={form.guest_id}
          onChange={(v) => set("guest_id", v)}
          error={errors.guest_id}
          required
          options={(guests ?? []).map((g) => [String(g.id), `${g.full_name}${g.email ? ` — ${g.email}` : ""}`])}
        />
        <SelectField
          label="Coupon"
          name="coupon_id"
          value={form.coupon_id}
          onChange={(v) => set("coupon_id", v)}
          placeholder="No coupon"
          options={(coupons ?? [])
            .filter((c) => c.status === "active")
            .map((c) => [String(c.id), c.code])}
        />
        <DateField
          label="Check-in"
          name="check_in"
          value={form.check_in}
          onChange={(v) => set("check_in", v)}
          error={errors.check_in}
          required
        />
        <DateField
          label="Check-out"
          name="check_out"
          value={form.check_out}
          onChange={(v) => set("check_out", v)}
          error={errors.check_out}
          required
        />
        <TextField
          label="Adults"
          name="adults"
          value={form.adults}
          onChange={(v) => set("adults", v)}
          inputMode="numeric"
          required
        />
        <TextField
          label="Children"
          name="children"
          value={form.children}
          onChange={(v) => set("children", v)}
          inputMode="numeric"
        />
      </div>

      <div className="mt-4">
        <p className="mb-1.5 text-sm font-medium text-[#1E2B22]">
          Rooms <span className="text-[#C25B50]">*</span>
        </p>
        {errors.room_ids && (
          <p className="mb-1.5 text-xs text-[#B3453A]">{errors.room_ids}</p>
        )}
        {!form.check_in || !form.check_out ? (
          <p className="rounded-lg border border-dashed border-[#DCE3D5] px-4 py-5 text-center text-sm text-[#7A8677]">
            Pick check-in and check-out dates to see available rooms.
          </p>
        ) : availabilityQuery.isLoading ? (
          <p className="rounded-lg border border-[#DCE3D5] px-4 py-5 text-sm text-[#7A8677]">
            Checking availability…
          </p>
        ) : availableRooms.length === 0 ? (
          <p className="rounded-lg border border-[#C25B50]/20 bg-[#C25B50]/5 px-4 py-5 text-sm text-[#B3453A]">
            No rooms available for the selected dates.
          </p>
        ) : (
          <div className="grid max-h-48 grid-cols-1 gap-2 overflow-y-auto rounded-lg border border-[#DCE3D5] p-2">
            {availableRooms.map((room) => {
              const checked = form.room_ids.includes(room.id);
              return (
                <label
                  key={room.id}
                  className={`flex cursor-pointer items-center justify-between gap-3 rounded-lg border px-3 py-2.5 transition-colors ${
                    checked
                      ? "border-[#7FA35C] bg-[#7FA35C]/10"
                      : "border-[#DCE3D5] hover:border-[#7FA35C]/50"
                  }`}
                >
                  <div className="flex items-center gap-3">
                    <input
                      type="checkbox"
                      checked={checked}
                      onChange={() => toggleRoom(room.id)}
                      className="h-4 w-4 accent-[#7FA35C]"
                    />
                    <div>
                      <p className="text-sm font-medium text-[#1E2B22]">
                        Room {room.room_number}
                      </p>
                      <p className="text-xs text-[#7A8677]">
                        {room.room_type?.name} · {room.room_type?.bed_type} bed
                      </p>
                    </div>
                  </div>
                  <span className="text-sm font-medium text-[#4F7A3B]">
                    {formatCurrency(room.room_type?.base_price)}
                    <span className="text-xs font-normal text-[#7A8677]">/night</span>
                  </span>
                </label>
              );
            })}
          </div>
        )}
      </div>

      <div className="mt-4">
        <TextAreaField
          label="Special request"
          name="special_request"
          value={form.special_request}
          onChange={(v) => set("special_request", v)}
          placeholder="Any special requirements for this stay"
          maxLength={2000}
        />
      </div>

      <FormActions
        onCancel={onSuccess}
        submitLabel="Create booking"
        loading={mutation.isPending}
      />
    </form>
  );
}

export default function BookingsPage() {
  const queryClient = useQueryClient();
  const [page, setPage] = useState(1);
  const [search, setSearch] = useUrlQuerySearch();
  const [status, setStatus] = useState("");
  const [createOpen, setCreateOpen] = useState(false);
  const [detail, setDetail] = useState(null);
  const [actionTarget, setActionTarget] = useState(null);

  const bookingsQuery = useQuery({
    queryKey: ["bookings", page, search, status],
    queryFn: () =>
      fetchBookings({
        page,
        search: search || undefined,
        status: status || undefined,
      }),
  });

  const guestsQuery = useQuery({
    queryKey: ["guests-options"],
    queryFn: () => fetchGuests({ per_page: 100 }),
  });

  const couponsQuery = useQuery({
    queryKey: ["coupons-options"],
    queryFn: () => fetchCoupons({ per_page: 100 }),
  });

  const paymentsQuery = useQuery({
    queryKey: ["booking-payments", detail?.id],
    queryFn: () => fetchBookingPayments(detail.id),
    enabled: Boolean(detail?.id),
  });

  const statusAction = useMutation({
    mutationFn: ({ type, id }) => {
      if (type === "confirm") return confirmBooking(id);
      if (type === "complete") return completeBooking(id);
      return cancelBooking(id);
    },
    onSuccess: (_data, vars) => {
      toast.success(`Booking ${titleCase(vars.type)}d.`);
      queryClient.invalidateQueries({ queryKey: ["bookings"] });
      queryClient.invalidateQueries({ queryKey: ["booking-payments"] });
      queryClient.invalidateQueries({ queryKey: ["dashboard-summary"] });
      setActionTarget(null);
      setDetail((d) => (d ? { ...d, status: vars.type === "complete" ? "completed" : vars.type === "confirm" ? "confirmed" : "cancelled" } : d));
    },
    onError: (error) => toast.error(getErrorMessage(error, "Could not update booking.")),
  });

  function openDetail(row) {
    setDetail(row);
  }

  const columns = [
    {
      header: "Booking",
      cell: (r) => (
        <div className="flex items-center gap-3">
          <span className="flex h-10 w-10 shrink-0 items-center justify-center rounded-lg bg-[#16261F] text-[#9DBE7C]">
            <CalendarDays className="h-4 w-4" strokeWidth={1.5} />
          </span>
          <div>
            <p className="font-medium text-[#1E2B22]">{r.booking_code}</p>
            <p className="text-xs text-[#7A8677]">
              {formatDate(r.check_in)} → {formatDate(r.check_out)}
            </p>
          </div>
        </div>
      ),
    },
    {
      header: "Guest",
      cell: (r) => (
        <div className="flex items-center gap-2.5">
          <span className="flex h-8 w-8 shrink-0 items-center justify-center rounded-full bg-[#7FA35C]/12 text-xs font-semibold text-[#4F7A3B]">
            {initialsOf(r.guest?.full_name)}
          </span>
          <span className="truncate text-[#1E2B22]">{r.guest?.full_name ?? "—"}</span>
        </div>
      ),
    },
    {
      header: "Rooms",
      cell: (r) => (
        <span className="text-[#5E6B5A]">
          {(r.rooms ?? []).map((room) => room.room_number).join(", ") || "—"}
        </span>
      ),
    },
    {
      header: "Type",
      cell: (r) => (
        <span
          className={`inline-flex items-center rounded-md px-2 py-1 text-xs font-medium ${
            r.booking_source === "walk_in"
              ? "bg-[#F1F3ED] text-[#5E6B5A]"
              : "bg-[#7FA35C]/10 text-[#4F7A3B]"
          }`}
        >
          {bookingTypeLabel(r.booking_source)}
        </span>
      ),
    },
    {
      header: "Guests",
      cell: (r) => (
        <span className="inline-flex items-center gap-1.5 text-[#5E6B5A]">
          <UserIcon className="h-3.5 w-3.5" strokeWidth={1.5} />
          {r.adults} + {r.children ?? 0}
        </span>
      ),
    },
    {
      header: "Total",
      cell: (r) => (
        <span className="font-semibold text-[#1E2B22]">
          {formatCurrency(r.total_amount)}
        </span>
      ),
    },
    {
      header: "Deposit",
      cell: (r) => (
        <span className="text-[#5E6B5A]">
          {formatCurrency(r.deposit_amount ?? 0)}
          {r.deposit_rate != null && (
            <span className="ml-1 text-xs text-[#7A8677]">({r.deposit_rate}%)</span>
          )}
        </span>
      ),
    },
    {
      header: "Status",
      cell: (r) => <StatusBadge status={r.status} />,
    },
    {
      header: "",
      className: "text-right",
      cell: (r) => (
        <div className="flex items-center justify-end gap-1">
          <button
            type="button"
            onClick={() => openDetail(r)}
            aria-label="View booking"
            className="flex h-8 items-center gap-1.5 rounded-lg px-2 text-xs font-medium text-[#5E6B5A] transition-colors hover:bg-[#F1F3ED] hover:text-[#1E2B22]"
          >
            <Eye className="h-4 w-4" strokeWidth={1.5} />
            View
          </button>
          {r.status === "pending" && (
            <>
              <IconAction
                title="Confirm"
                onClick={() => setActionTarget({ type: "confirm", booking: r })}
                icon={<Check className="h-4 w-4" strokeWidth={1.5} />}
                className="text-[#4F7A3B] hover:bg-[#7FA35C]/10"
                label="Confirm"
              />
              <IconAction
                title="Cancel"
                onClick={() => setActionTarget({ type: "cancel", booking: r })}
                icon={<X className="h-4 w-4" strokeWidth={1.5} />}
                className="text-[#B3453A] hover:bg-[#C25B50]/10"
                label="Cancel"
              />
            </>
          )}
          {r.status === "confirmed" && (
            <>
              <IconAction
                title="Complete"
                onClick={() => setActionTarget({ type: "complete", booking: r })}
                icon={<PartyPopper className="h-4 w-4" strokeWidth={1.5} />}
                className="text-[#4F7A3B] hover:bg-[#7FA35C]/10"
                label="Complete"
              />
              <IconAction
                title="Cancel"
                onClick={() => setActionTarget({ type: "cancel", booking: r })}
                icon={<X className="h-4 w-4" strokeWidth={1.5} />}
                className="text-[#B3453A] hover:bg-[#C25B50]/10"
                label="Cancel"
              />
            </>
          )}
        </div>
      ),
    },
  ];

  if (bookingsQuery.isError) {
    return <ErrorState message="Could not load bookings." onRetry={bookingsQuery.refetch} />;
  }

  const { items = [], meta = {} } = bookingsQuery.data ?? {};
  const payments = paymentsQuery.data?.items ?? [];

  return (
    <div>
      <PageHeader
        title="Bookings"
        description="Reservations across your property, from request to checkout."
      />

      <div className="mb-4 flex flex-wrap items-center gap-2.5">
        <SearchInput
          value={search}
          onChange={(v) => {
            setSearch(v);
            setPage(1);
          }}
          placeholder="Search bookings…"
        />
        <select
          value={status}
          onChange={(e) => {
            setStatus(e.target.value);
            setPage(1);
          }}
          className="h-10 rounded-lg border border-[#DCE3D5] bg-white px-3 text-sm text-[#5E6B5A] outline-none focus:border-[#7FA35C]"
        >
          <option value="">All statuses</option>
          {STATUSES.map((s) => (
            <option key={s} value={s}>
              {titleCase(s)}
            </option>
          ))}
        </select>
        <div className="ml-auto">
          <button
            type="button"
            onClick={() => setCreateOpen(true)}
            className="flex h-10 items-center gap-2 rounded-lg bg-[#16261F] px-4 text-sm font-medium text-[#F2F5EC] transition-all duration-200 hover:bg-[#20372C] hover:shadow-[0_0_0_3px_rgba(127,163,92,0.3)]"
          >
            <Plus className="h-4 w-4" strokeWidth={1.5} />
            New booking
          </button>
        </div>
      </div>

      {bookingsQuery.isLoading ? (
        <LoadingState label="Loading bookings…" />
      ) : (
        <>
          <DataTable
            columns={columns}
            data={items}
            loading={bookingsQuery.isLoading}
            emptyTitle="No bookings found"
            emptyDescription="Create a booking to get started."
            minWidth={1180}
            onRowDoubleClick={openDetail}
          />
          <div className="mt-3 rounded-xl border border-[#DCE3D5] bg-white">
            <Pagination
              current={page}
              total={meta.total ?? 0}
              perPage={meta.per_page ?? 20}
              onPageChange={setPage}
            />
          </div>
        </>
      )}

      <FormModal
        open={createOpen}
        onOpenChange={setCreateOpen}
        title="New booking"
        description="Reserve one or more rooms for a guest."
      >
        <BookingCreateForm
          guests={guestsQuery.data?.items ?? []}
          coupons={couponsQuery.data?.items ?? []}
          onSuccess={() => setCreateOpen(false)}
        />
      </FormModal>

      <Sheet open={Boolean(detail)} onOpenChange={(open) => !open && setDetail(null)}>
        <SheetContent className="w-full sm:max-w-md">
          {detail && (
            <>
              <SheetHeader className="pb-2">
                <SheetTitle className="text-lg" style={{ fontFamily: "'Fraunces', serif" }}>
                  {detail.booking_code}
                </SheetTitle>
                <SheetDescription>
                  Created {formatDateTime(detail.created_at)}
                </SheetDescription>
                <div className="pt-1">
                  <StatusBadge status={detail.status} />
                </div>
              </SheetHeader>

              <div className="flex-1 space-y-6 overflow-y-auto px-4 pb-4">
                <DetailBlock title="Guest">
                  <div className="flex items-center gap-3">
                    <span className="flex h-10 w-10 items-center justify-center rounded-full bg-[#7FA35C]/12 text-sm font-semibold text-[#4F7A3B]">
                      {initialsOf(detail.guest?.full_name)}
                    </span>
                    <div>
                      <p className="text-sm font-medium text-[#1E2B22]">
                        {detail.guest?.full_name}
                      </p>
                      <p className="text-xs text-[#7A8677]">
                        {detail.guest?.email ?? "No email"} · {detail.guest?.phone ?? "No phone"}
                      </p>
                    </div>
                  </div>
                </DetailBlock>

                <DetailBlock title="Stay">
                  <div className="grid grid-cols-2 gap-3 text-sm">
                    <DetailItem label="Check-in" value={formatDate(detail.check_in)} />
                    <DetailItem label="Check-out" value={formatDate(detail.check_out)} />
                    <DetailItem label="Adults" value={detail.adults} />
                    <DetailItem label="Children" value={detail.children ?? 0} />
                    <DetailItem label="Type" value={bookingTypeLabel(detail.booking_source)} />
                  </div>
                </DetailBlock>

                <DetailBlock title="Rooms">
                  <div className="space-y-2">
                    {(detail.rooms ?? []).map((room, i) => (
                      <div key={i} className="flex items-center justify-between rounded-lg border border-[#EEF1E9] px-3 py-2.5">
                        <div className="flex items-center gap-2.5">
                          <BedDouble className="h-4 w-4 text-[#7A8677]" strokeWidth={1.5} />
                          <div>
                            <p className="text-sm font-medium text-[#1E2B22]">
                              Room {room.room_number}
                            </p>
                            <p className="text-xs text-[#7A8677]">
                              {room.room_type?.name ?? "—"}
                            </p>
                          </div>
                        </div>
                        {detail.booking_items?.[i] && (
                          <span className="text-xs text-[#7A8677]">
                            {formatCurrency(detail.booking_items[i].subtotal)}
                          </span>
                        )}
                      </div>
                    ))}
                  </div>
                </DetailBlock>

                <DetailBlock title="Payments">
                  {payments.length ? (
                    <div className="space-y-2">
                      {payments.map((p) => (
                        <div key={p.id} className="flex items-center justify-between rounded-lg border border-[#EEF1E9] px-3 py-2.5">
                          <div>
                            <p className="text-sm font-medium text-[#1E2B22]">
                              {formatCurrency(p.amount)} · {titleCase(p.payment_method)}
                            </p>
                            <p className="text-xs text-[#7A8677]">
                              {p.transaction_id ?? "No transaction ID"}
                            </p>
                          </div>
                          <StatusBadge status={p.status} />
                        </div>
                      ))}
                    </div>
                  ) : (
                    <p className="text-sm text-[#7A8677]">No payments recorded yet.</p>
                  )}
                </DetailBlock>

                {detail.special_request && (
                  <DetailBlock title="Special request">
                    <p className="text-sm leading-relaxed text-[#5E6B5A]">
                      {detail.special_request}
                    </p>
                  </DetailBlock>
                )}

                <div className="space-y-2 rounded-lg bg-[#F8F9F4] px-4 py-3">
                  <div className="flex items-center justify-between text-sm">
                    <span className="text-[#7A8677]">
                      Deposit ({detail.deposit_rate ?? "—"}%)
                    </span>
                    <span className="font-medium text-[#1E2B22]">
                      {formatCurrency(detail.deposit_amount ?? 0)}
                    </span>
                  </div>
                  <div className="flex items-center justify-between border-t border-[#E4E9DF] pt-2">
                    <span className="text-sm text-[#7A8677]">Total</span>
                    <span className="text-lg font-semibold text-[#1E2B22]">
                      {formatCurrency(detail.total_amount)}
                    </span>
                  </div>
                </div>
              </div>
            </>
          )}
        </SheetContent>
      </Sheet>

      <ConfirmDialog
        open={Boolean(actionTarget)}
        onOpenChange={(open) => !open && setActionTarget(null)}
        variant={actionTarget?.type === "cancel" ? "danger" : "default"}
        title={
          actionTarget?.type === "confirm"
            ? "Confirm this booking?"
            : actionTarget?.type === "complete"
              ? "Complete this booking?"
              : "Cancel this booking?"
        }
        description={
          actionTarget?.type === "cancel"
            ? "The guest will be notified. This cannot be undone."
            : `Booking ${actionTarget?.booking?.booking_code} will be marked as ${titleCase(actionTarget?.type + "ed")}.`
        }
        confirmLabel={titleCase(actionTarget?.type ?? "confirm")}
        loading={statusAction.isPending}
        onConfirm={() =>
          actionTarget && statusAction.mutate({ type: actionTarget.type, id: actionTarget.booking.id })
        }
      />
    </div>
  );
}

function IconAction({ icon, onClick, className = "", title, label }) {
  return (
    <button
      type="button"
      title={title}
      onClick={onClick}
      className={`flex h-8 items-center gap-1.5 rounded-lg px-2 text-xs font-medium transition-colors ${className}`}
    >
      {icon}
      <span className="hidden 2xl:inline">{label}</span>
    </button>
  );
}

function DetailBlock({ title, children }) {
  return (
    <div>
      <p className="mb-2 text-xs font-semibold uppercase tracking-wider text-[#7A8677]">
        {title}
      </p>
      {children}
    </div>
  );
}

function DetailItem({ label, value }) {
  return (
    <div>
      <p className="text-xs text-[#7A8677]">{label}</p>
      <p className="text-sm font-medium text-[#1E2B22]">{value}</p>
    </div>
  );
}