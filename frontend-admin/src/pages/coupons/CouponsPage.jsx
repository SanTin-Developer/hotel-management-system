import { useState } from "react";
import { useQuery, useMutation, useQueryClient } from "@tanstack/react-query";
import { Plus, Pencil, Trash2, Tag as TagIcon } from "lucide-react";
import { toast } from "sonner";

import PageHeader from "@/components/PageHeader";
import DataTable from "@/components/DataTable";
import SearchInput from "@/components/SearchInput";
import StatusBadge from "@/components/StatusBadge";
import Pagination from "@/components/Pagination";
import ConfirmDialog from "@/components/ConfirmDialog";
import FormModal, { FormActions } from "@/components/FormModal";
import { LoadingState, ErrorState } from "@/components/States";
import { TextField, SelectField, DateField } from "@/components/form/Inputs";
import { fetchCoupons, createCoupon, updateCoupon, deleteCoupon } from "@/services/api/coupons";
import { getErrorMessage, formatCurrency, formatDateTime, titleCase } from "@/lib/format";

const DISCOUNT_TYPES = [
  ["percentage", "Percentage"],
  ["fixed", "Fixed amount"],
];

const STATUSES = [
  ["active", "Active"],
  ["inactive", "Inactive"],
];

function discountLabel(coupon) {
  if (coupon.discount_type === "percentage") return `${coupon.discount_value}%`;
  return formatCurrency(coupon.discount_value);
}

const EMPTY_FORM = {
  code: "",
  discount_type: "percentage",
  discount_value: "",
  min_amount: "",
  start_date: new Date().toISOString().slice(0, 10),
  end_date: "",
  usage_limit: "",
  status: "active",
};

function CouponForm({ coupon, onSuccess }) {
  const queryClient = useQueryClient();
  const isEdit = Boolean(coupon);
  const [form, setForm] = useState(
    isEdit
      ? {
          code: coupon.code,
          discount_type: coupon.discount_type,
          discount_value: String(coupon.discount_value),
          min_amount: String(coupon.min_amount ?? ""),
          start_date: (coupon.start_date ?? "").slice(0, 10),
          end_date: (coupon.end_date ?? "").slice(0, 10),
          usage_limit: String(coupon.usage_limit ?? ""),
          status: coupon.status,
        }
      : EMPTY_FORM,
  );
  const [errors, setErrors] = useState({});

  const mutation = useMutation({
    mutationFn: (data) => (isEdit ? updateCoupon(coupon.id, data) : createCoupon(data)),
    onSuccess: () => {
      toast.success(isEdit ? "Coupon updated." : "Coupon created.");
      queryClient.invalidateQueries({ queryKey: ["coupons"] });
      queryClient.invalidateQueries({ queryKey: ["coupons-options"] });
      onSuccess();
    },
    onError: (error) => toast.error(getErrorMessage(error, "Could not save coupon.")),
  });

  function set(field, value) {
    setForm((f) => ({ ...f, [field]: value }));
    setErrors((e) => ({ ...e, [field]: undefined }));
  }

  function handleSubmit(e) {
    e.preventDefault();
    const errs = {};
    if (!form.code.trim()) errs.code = "Code is required.";
    if (form.discount_value === "" || Number(form.discount_value) < 0) {
      errs.discount_value = "Enter a valid discount value.";
    }
    if (!form.start_date) errs.start_date = "Start date is required.";
    if (!form.end_date) errs.end_date = "End date is required.";
    if (form.start_date && form.end_date && form.end_date < form.start_date) {
      errs.end_date = "End date must be after start date.";
    }
    setErrors(errs);
    if (Object.keys(errs).length) return;
    mutation.mutate({
      code: form.code.trim().toUpperCase(),
      discount_type: form.discount_type,
      discount_value: Number(form.discount_value),
      min_amount: form.min_amount ? Number(form.min_amount) : null,
      start_date: form.start_date,
      end_date: form.end_date,
      usage_limit: form.usage_limit ? Number(form.usage_limit) : null,
      status: form.status,
    });
  }

  return (
    <form onSubmit={handleSubmit}>
      <div className="grid grid-cols-1 gap-4 sm:grid-cols-2">
        <TextField
          label="Code"
          name="code"
          value={form.code}
          onChange={(v) => set("code", v)}
          error={errors.code}
          required
          placeholder="SAVE10"
        />
        <SelectField
          label="Discount type"
          name="discount_type"
          value={form.discount_type}
          onChange={(v) => set("discount_type", v)}
          options={DISCOUNT_TYPES}
        />
        <TextField
          label={form.discount_type === "percentage" ? "Discount (%)" : "Discount (USD)"}
          name="discount_value"
          value={form.discount_value}
          onChange={(v) => set("discount_value", v)}
          error={errors.discount_value}
          required
          inputMode="decimal"
        />
        <TextField
          label="Minimum spend"
          name="min_amount"
          value={form.min_amount}
          onChange={(v) => set("min_amount", v)}
          hint="Leave empty for no minimum."
          inputMode="decimal"
        />
        <DateField
          label="Start date"
          name="start_date"
          value={form.start_date}
          onChange={(v) => set("start_date", v)}
          error={errors.start_date}
          required
        />
        <DateField
          label="End date"
          name="end_date"
          value={form.end_date}
          onChange={(v) => set("end_date", v)}
          error={errors.end_date}
          required
        />
        <TextField
          label="Usage limit"
          name="usage_limit"
          value={form.usage_limit}
          onChange={(v) => set("usage_limit", v)}
          hint="Leave empty for unlimited uses."
          inputMode="numeric"
        />
        <SelectField
          label="Status"
          name="status"
          value={form.status}
          onChange={(v) => set("status", v)}
          options={STATUSES}
        />
      </div>
      <FormActions
        onCancel={onSuccess}
        submitLabel={isEdit ? "Save changes" : "Create coupon"}
        loading={mutation.isPending}
      />
    </form>
  );
}

export default function CouponsPage() {
  const queryClient = useQueryClient();
  const [page, setPage] = useState(1);
  const [search, setSearch] = useState("");
  const [status, setStatus] = useState("");
  const [type, setType] = useState("");
  const [formOpen, setFormOpen] = useState(false);
  const [editing, setEditing] = useState(null);
  const [deleting, setDeleting] = useState(null);

  const query = useQuery({
    queryKey: ["coupons", page, search, status, type],
    queryFn: () =>
      fetchCoupons({
        page,
        search: search || undefined,
        status: status || undefined,
        discount_type: type || undefined,
      }),
  });

  const deleteMutation = useMutation({
    mutationFn: deleteCoupon,
    onSuccess: () => {
      toast.success("Coupon deleted.");
      queryClient.invalidateQueries({ queryKey: ["coupons"] });
      queryClient.invalidateQueries({ queryKey: ["coupons-options"] });
      setDeleting(null);
    },
    onError: (error) => toast.error(getErrorMessage(error, "Could not delete coupon.")),
  });

  const columns = [
    {
      header: "Code",
      cell: (r) => (
        <div className="flex items-center gap-3">
          <span className="flex h-10 w-10 shrink-0 items-center justify-center rounded-lg bg-[#D9A441]/12 text-[#A67C16]">
            <TagIcon className="h-4 w-4" strokeWidth={1.5} />
          </span>
          <div>
            <p className="font-semibold uppercase tracking-wide text-[#1E2B22]">{r.code}</p>
            <p className="text-xs text-[#7A8677]">{titleCase(r.discount_type)}</p>
          </div>
        </div>
      ),
    },
    {
      header: "Discount",
      cell: (r) => <span className="font-medium text-[#1E2B22]">{discountLabel(r)}</span>,
    },
    {
      header: "Min. spend",
      cell: (r) => <span>{r.min_amount ? formatCurrency(r.min_amount) : "—"}</span>,
    },
    {
      header: "Valid from → to",
      cell: (r) => (
        <div className="text-xs">
          <p className="text-[#5E6B5A]">{formatDateTime(r.start_date)}</p>
          <p className="text-[#7A8677]">{formatDateTime(r.end_date)}</p>
        </div>
      ),
    },
    {
      header: "Uses",
      cell: (r) => (
        <span className="inline-flex items-center rounded-md bg-[#F1F3ED] px-2 py-1 text-xs font-medium text-[#5E6B5A]">
          {r.bookings_count ?? 0}
          {r.usage_limit ? ` / ${r.usage_limit}` : ""}
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
            onClick={() => {
              setEditing(r);
              setFormOpen(true);
            }}
            aria-label="Edit coupon"
            className="flex h-8 w-8 items-center justify-center rounded-lg text-[#7A8677] transition-colors hover:bg-[#F1F3ED] hover:text-[#1E2B22]"
          >
            <Pencil className="h-4 w-4" strokeWidth={1.5} />
          </button>
          <button
            type="button"
            onClick={() => setDeleting(r)}
            aria-label="Delete coupon"
            className="flex h-8 w-8 items-center justify-center rounded-lg text-[#7A8677] transition-colors hover:bg-[#C25B50]/10 hover:text-[#B3453A]"
          >
            <Trash2 className="h-4 w-4" strokeWidth={1.5} />
          </button>
        </div>
      ),
    },
  ];

  if (query.isError) {
    return <ErrorState message="Could not load coupons." onRetry={query.refetch} />;
  }

  const { items = [], meta = {} } = query.data ?? {};

  return (
    <div>
      <PageHeader
        title="Coupons"
        description="Discount codes guests can apply to their bookings."
      />

      <div className="mb-4 flex flex-wrap items-center gap-2.5">
        <SearchInput
          value={search}
          onChange={(v) => {
            setSearch(v);
            setPage(1);
          }}
          placeholder="Search coupons…"
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
          {STATUSES.map(([v, l]) => (
            <option key={v} value={v}>
              {l}
            </option>
          ))}
        </select>
        <select
          value={type}
          onChange={(e) => {
            setType(e.target.value);
            setPage(1);
          }}
          className="h-10 rounded-lg border border-[#DCE3D5] bg-white px-3 text-sm text-[#5E6B5A] outline-none focus:border-[#7FA35C]"
        >
          <option value="">All types</option>
          {DISCOUNT_TYPES.map(([v, l]) => (
            <option key={v} value={v}>
              {l}
            </option>
          ))}
        </select>
        <div className="ml-auto">
          <button
            type="button"
            onClick={() => {
              setEditing(null);
              setFormOpen(true);
            }}
            className="flex h-10 items-center gap-2 rounded-lg bg-[#16261F] px-4 text-sm font-medium text-[#F2F5EC] transition-all duration-200 hover:bg-[#20372C] hover:shadow-[0_0_0_3px_rgba(127,163,92,0.3)]"
          >
            <Plus className="h-4 w-4" strokeWidth={1.5} />
            Add coupon
          </button>
        </div>
      </div>

      {query.isLoading ? (
        <LoadingState label="Loading coupons…" />
      ) : (
        <>
          <DataTable
            columns={columns}
            data={items}
            loading={query.isLoading}
            emptyTitle="No coupons found"
            emptyDescription="Create a promo code to attract more bookings."
          />
          <div className="mt-3 rounded-xl border border-[#DCE3D5] bg-white">
            <Pagination
              current={page}
              total={meta.total ?? 0}
              perPage={meta.per_page ?? 15}
              onPageChange={setPage}
            />
          </div>
        </>
      )}

      <FormModal
        open={formOpen}
        onOpenChange={(open) => {
          setFormOpen(open);
          if (!open) setEditing(null);
        }}
        title={editing ? `Edit coupon ${editing.code}` : "Create a coupon"}
      >
        <CouponForm
          key={editing?.id ?? "new"}
          coupon={editing}
          onSuccess={() => {
            setFormOpen(false);
            setEditing(null);
          }}
        />
      </FormModal>

      <ConfirmDialog
        open={Boolean(deleting)}
        onOpenChange={(open) => !open && setDeleting(null)}
        title={`Delete coupon ${deleting?.code ?? ""}?`}
        description="The code will stop working immediately. Past bookings are unaffected."
        confirmLabel="Delete coupon"
        loading={deleteMutation.isPending}
        onConfirm={() => deleting && deleteMutation.mutate(deleting.id)}
      />
    </div>
  );
}