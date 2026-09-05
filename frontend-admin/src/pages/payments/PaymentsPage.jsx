import { useState } from "react";
import { useQuery, useMutation, useQueryClient } from "@tanstack/react-query";
import { Plus, Eye, Wallet } from "lucide-react";
import { toast } from "sonner";

import { Sheet, SheetContent, SheetHeader, SheetTitle, SheetDescription } from "@/components/ui/sheet";

import PageHeader from "@/components/PageHeader";
import DataTable from "@/components/DataTable";
import SearchInput from "@/components/SearchInput";
import StatusBadge from "@/components/StatusBadge";
import Pagination from "@/components/Pagination";
import FormModal, { FormActions } from "@/components/FormModal";
import { LoadingState, ErrorState } from "@/components/States";
import { TextField, SelectField } from "@/components/form/Inputs";
import { fetchBookings, fetchBookingPayments } from "@/services/api/bookings";
import { createPayment, markPaid, markFailed, refundPayment } from "@/services/api/payments";
import { getErrorMessage, formatCurrency, formatDateTime, titleCase } from "@/lib/format";

const METHODS = [
  ["cash", "Cash"],
  ["card", "Card"],
  ["bank_transfer", "Bank transfer"],
  ["online", "Online"],
];

function PaymentForm({ booking, onSuccess }) {
  const queryClient = useQueryClient();
  const [form, setForm] = useState({
    booking_id: String(booking.id),
    amount: "",
    payment_method: "cash",
    transaction_id: "",
  });
  const [errors, setErrors] = useState({});

  const mutation = useMutation({
    mutationFn: createPayment,
    onSuccess: () => {
      toast.success("Payment recorded.");
      queryClient.invalidateQueries({ queryKey: ["booking-payments"] });
      queryClient.invalidateQueries({ queryKey: ["dashboard-summary"] });
      onSuccess();
    },
    onError: (error) => toast.error(getErrorMessage(error, "Could not record payment.")),
  });

  function set(field, value) {
    setForm((f) => ({ ...f, [field]: value }));
    setErrors((e) => ({ ...e, [field]: undefined }));
  }

  function handleSubmit(e) {
    e.preventDefault();
    const errs = {};
    if (form.amount === "" || Number(form.amount) <= 0) errs.amount = "Enter a valid amount.";
    if (form.payment_method !== "cash" && !form.transaction_id.trim()) {
      errs.transaction_id = "Transaction ID is required for this method.";
    }
    setErrors(errs);
    if (Object.keys(errs).length) return;
    mutation.mutate({
      booking_id: Number(form.booking_id),
      amount: Number(form.amount),
      payment_method: form.payment_method,
      transaction_id: form.transaction_id.trim() || undefined,
    });
  }

  return (
    <form onSubmit={handleSubmit}>
      <div className="rounded-lg border border-[#EEF1E9] bg-[#F8F9F4] px-4 py-3">
        <p className="text-sm font-medium text-[#1E2B22]">{booking.booking_code}</p>
        <p className="text-xs text-[#7A8677]">
          {booking.guest?.full_name} · Total {formatCurrency(booking.total_amount)}
        </p>
      </div>
      <div className="mt-4 grid grid-cols-1 gap-4 sm:grid-cols-2">
        <TextField
          label="Amount"
          name="amount"
          value={form.amount}
          onChange={(v) => set("amount", v)}
          error={errors.amount}
          required
          inputMode="decimal"
          placeholder="150.00"
        />
        <SelectField
          label="Payment method"
          name="payment_method"
          value={form.payment_method}
          onChange={(v) => set("payment_method", v)}
          options={METHODS}
        />
      </div>
      <div className="mt-4">
        <TextField
          label="Transaction ID"
          name="transaction_id"
          value={form.transaction_id}
          onChange={(v) => set("transaction_id", v)}
          error={errors.transaction_id}
          hint="Required for card, bank transfer and online payments."
          placeholder="TXN-000123"
        />
      </div>
      <FormActions
        onCancel={onSuccess}
        submitLabel="Record payment"
        loading={mutation.isPending}
      />
    </form>
  );
}

export default function PaymentsPage() {
  const queryClient = useQueryClient();
  const [page, setPage] = useState(1);
  const [search, setSearch] = useState("");
  const [detail, setDetail] = useState(null);
  const [payTarget, setPayTarget] = useState(null);

  const bookingsQuery = useQuery({
    queryKey: ["payments-bookings", page, search],
    queryFn: () => fetchBookings({ page, search: search || undefined }),
  });

  const paymentsQuery = useQuery({
    queryKey: ["booking-payments", detail?.id],
    queryFn: () => fetchBookingPayments(detail.id),
    enabled: Boolean(detail?.id),
    staleTime: 0,
  });

  const paidMutation = useMutation({
    mutationFn: ({ id, transactionId }) => markPaid(id, transactionId),
    onSuccess: () => {
      toast.success("Payment marked as paid.");
      refreshPayments();
    },
    onError: (error) => toast.error(getErrorMessage(error, "Could not update payment.")),
  });

  const failedMutation = useMutation({
    mutationFn: markFailed,
    onSuccess: () => {
      toast.success("Payment marked as failed.");
      refreshPayments();
    },
    onError: (error) => toast.error(getErrorMessage(error, "Could not update payment.")),
  });

  const refundMutation = useMutation({
    mutationFn: ({ id, transactionId }) => refundPayment(id, transactionId),
    onSuccess: () => {
      toast.success("Payment refunded.");
      refreshPayments();
    },
    onError: (error) => toast.error(getErrorMessage(error, "Could not refund payment.")),
  });

  function refreshPayments() {
    queryClient.invalidateQueries({ queryKey: ["booking-payments"] });
    queryClient.invalidateQueries({ queryKey: ["payments-bookings"] });
    queryClient.invalidateQueries({ queryKey: ["dashboard-summary"] });
  }

  const columns = [
    {
      header: "Booking",
      cell: (r) => (
        <div>
          <p className="font-medium text-[#1E2B22]">{r.booking_code}</p>
          <p className="text-xs text-[#7A8677]">
            {formatDateTime(r.created_at)}
          </p>
        </div>
      ),
    },
    {
      header: "Guest",
      cell: (r) => <span className="text-[#5E6B5A]">{r.guest?.full_name ?? "—"}</span>,
    },
    {
      header: "Total amount",
      cell: (r) => (
        <span className="font-semibold text-[#1E2B22]">{formatCurrency(r.total_amount)}</span>
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
            onClick={() => setPayTarget(r)}
            className="flex h-8 items-center gap-1.5 rounded-lg bg-[#16261F] px-3 text-xs font-medium text-[#F2F5EC] transition-colors hover:bg-[#20372C]"
          >
            <Plus className="h-3.5 w-3.5" strokeWidth={1.5} />
            Record payment
          </button>
          <button
            type="button"
            onClick={() => setDetail(r)}
            aria-label="View payments"
            className="flex h-8 w-8 items-center justify-center rounded-lg text-[#7A8677] transition-colors hover:bg-[#F1F3ED] hover:text-[#1E2B22]"
          >
            <Eye className="h-4 w-4" strokeWidth={1.5} />
          </button>
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
        title="Payments"
        description="Record and manage payments against bookings."
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
        <div className="ml-auto hidden sm:block">
          <p className="text-sm text-[#7A8677]">
            Payments are recorded against bookings.
          </p>
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
            emptyDescription="Bookings appear here so you can record their payments."
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
        open={Boolean(payTarget)}
        onOpenChange={(open) => !open && setPayTarget(null)}
        title="Record a payment"
        description="Enter the amount and method for this payment."
      >
        {payTarget && (
          <PaymentForm booking={payTarget} onSuccess={() => setPayTarget(null)} />
        )}
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
                  Payments received for this booking.
                </SheetDescription>
                <StatusBadge status={detail.status} className="w-fit" />
              </SheetHeader>

              <div className="flex-1 space-y-4 overflow-y-auto px-4 pb-4">
                {payments.length === 0 && (
                  <div className="rounded-xl border border-dashed border-[#DCE3D5] px-4 py-8 text-center">
                    <Wallet className="mx-auto h-6 w-6 text-[#A8B39F]" strokeWidth={1.5} />
                    <p className="mt-2 text-sm text-[#7A8677]">
                      No payments recorded for this booking.
                    </p>
                  </div>
                )}

                {payments.map((p) => (
                  <div key={p.id} className="rounded-xl border border-[#EEF1E9] bg-white p-4">
                    <div className="flex items-center justify-between">
                      <p className="text-base font-semibold text-[#1E2B22]">
                        {formatCurrency(p.amount)}
                      </p>
                      <StatusBadge status={p.status} />
                    </div>
                    <div className="mt-2 space-y-0.5 text-xs text-[#7A8677]">
                      <p>Method · {titleCase(p.payment_method)}</p>
                      <p>Transaction · {p.transaction_id ?? "—"}</p>
                      {p.paid_at && <p>Paid · {formatDateTime(p.paid_at)}</p>}
                    </div>

                    {p.status === "pending" && (
                      <div className="mt-3 flex gap-2">
                        <button
                          type="button"
                          onClick={() => paidMutation.mutate({ id: p.id })}
                          disabled={paidMutation.isPending}
                          className="flex-1 rounded-lg bg-[#16261F] px-3 py-2 text-xs font-medium text-[#F2F5EC] transition-colors hover:bg-[#20372C] disabled:opacity-50"
                        >
                          Mark paid
                        </button>
                        <button
                          type="button"
                          onClick={() => failedMutation.mutate(p.id)}
                          disabled={failedMutation.isPending}
                          className="flex-1 rounded-lg border border-[#C25B50]/30 px-3 py-2 text-xs font-medium text-[#B3453A] transition-colors hover:bg-[#C25B50]/10 disabled:opacity-50"
                        >
                          Mark failed
                        </button>
                      </div>
                    )}

                    {p.status === "paid" && (
                      <button
                        type="button"
                        onClick={() => refundMutation.mutate({ id: p.id })}
                        disabled={refundMutation.isPending}
                        className="mt-3 w-full rounded-lg border border-[#DCE3D5] px-3 py-2 text-xs font-medium text-[#5E6B5A] transition-colors hover:bg-[#F1F3ED] disabled:opacity-50"
                      >
                        Refund payment
                      </button>
                    )}
                  </div>
                ))}
              </div>
            </>
          )}
        </SheetContent>
      </Sheet>
    </div>
  );
}