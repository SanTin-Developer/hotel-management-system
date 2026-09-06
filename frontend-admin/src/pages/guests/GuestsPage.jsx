import { useState } from "react";
import { useQuery, useMutation, useQueryClient } from "@tanstack/react-query";
import { Plus, Pencil, Trash2, Mail, Phone } from "lucide-react";
import { toast } from "sonner";

import PageHeader from "@/components/PageHeader";
import DataTable from "@/components/DataTable";
import SearchInput from "@/components/SearchInput";
import DebouncedInput from "@/components/DebouncedInput";
import useUrlQuerySearch from "@/hooks/useUrlQuerySearch";
import Pagination from "@/components/Pagination";
import ConfirmDialog from "@/components/ConfirmDialog";
import DetailModal from "@/components/DetailModal";
import FormModal, { FormActions } from "@/components/FormModal";
import { LoadingState, ErrorState } from "@/components/States";
import { TextField, SelectField, DateField, TextAreaField } from "@/components/form/Inputs";
import { fetchGuests, createGuest, updateGuest, deleteGuest } from "@/services/api/guests";
import { getErrorMessage, formatDate, initialsOf } from "@/lib/format";

const ID_TYPES = [
  ["national_id", "National ID"],
  ["passport", "Passport"],
];

const GENDERS = [
  ["male", "Male"],
  ["female", "Female"],
  ["other", "Other"],
];

const EMPTY_FORM = {
  full_name: "",
  email: "",
  phone: "",
  address: "",
  nationality: "",
  id_type: "passport",
  id_number: "",
  gender: "male",
  date_of_birth: "",
  country: "",
};

function GuestForm({ guest, onSuccess }) {
  const queryClient = useQueryClient();
  const isEdit = Boolean(guest);
  const [form, setForm] = useState(
    isEdit
      ? {
          full_name: guest.full_name,
          email: guest.email ?? "",
          phone: guest.phone ?? "",
          address: guest.address ?? "",
          nationality: guest.nationality ?? "",
          id_type: guest.id_type ?? "passport",
          id_number: guest.id_number ?? "",
          gender: guest.gender ?? "male",
          date_of_birth: guest.date_of_birth ?? "",
          country: guest.country ?? "",
        }
      : EMPTY_FORM,
  );
  const [errors, setErrors] = useState({});

  const mutation = useMutation({
    mutationFn: (data) => (isEdit ? updateGuest(guest.id, data) : createGuest(data)),
    onSuccess: () => {
      toast.success(isEdit ? "Guest updated." : "Guest created.");
      queryClient.invalidateQueries({ queryKey: ["guests"] });
      queryClient.invalidateQueries({ queryKey: ["guests-options"] });
      onSuccess();
    },
    onError: (error) => toast.error(getErrorMessage(error, "Could not save guest.")),
  });

  function set(field, value) {
    setForm((f) => ({ ...f, [field]: value }));
    setErrors((e) => ({ ...e, [field]: undefined }));
  }

  function handleSubmit(e) {
    e.preventDefault();
    const errs = {};
    if (!form.full_name.trim()) errs.full_name = "Full name is required.";
    if (form.email && !/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(form.email)) {
      errs.email = "Enter a valid email.";
    }
    if (form.phone && !/^[\d+\-()\s]{6,20}$/.test(form.phone)) {
      errs.phone = "Enter a valid phone number.";
    }
    setErrors(errs);
    if (Object.keys(errs).length) return;
    mutation.mutate({
      full_name: form.full_name.trim(),
      email: form.email || null,
      phone: form.phone || null,
      address: form.address || null,
      nationality: form.nationality || null,
      id_type: form.id_type,
      id_number: form.id_number || null,
      gender: form.gender,
      date_of_birth: form.date_of_birth || null,
      country: form.country || null,
    });
  }

  return (
    <form onSubmit={handleSubmit}>
      <div className="grid grid-cols-1 gap-4 sm:grid-cols-2">
        <TextField
          label="Full name"
          name="full_name"
          value={form.full_name}
          onChange={(v) => set("full_name", v)}
          error={errors.full_name}
          required
          placeholder="Sokha Chan"
        />
        <TextField
          label="Email"
          name="email"
          type="email"
          value={form.email}
          onChange={(v) => set("email", v)}
          error={errors.email}
          placeholder="guest@example.com"
          autoComplete="off"
        />
        <TextField
          label="Phone"
          name="phone"
          value={form.phone}
          onChange={(v) => set("phone", v)}
          error={errors.phone}
          placeholder="+855 12 345 678"
        />
        <TextField
          label="Nationality"
          name="nationality"
          value={form.nationality}
          onChange={(v) => set("nationality", v)}
          placeholder="Cambodian"
        />
        <SelectField
          label="ID type"
          name="id_type"
          value={form.id_type}
          onChange={(v) => set("id_type", v)}
          options={ID_TYPES}
        />
        <TextField
          label="ID number"
          name="id_number"
          value={form.id_number}
          onChange={(v) => set("id_number", v)}
          placeholder="Passport / national ID no."
        />
        <SelectField
          label="Gender"
          name="gender"
          value={form.gender}
          onChange={(v) => set("gender", v)}
          options={GENDERS}
        />
        <DateField
          label="Date of birth"
          name="date_of_birth"
          value={form.date_of_birth}
          onChange={(v) => set("date_of_birth", v)}
          max={new Date().toISOString().slice(0, 10)}
        />
      </div>
      <div className="mt-4">
        <TextField
          label="Country"
          name="country"
          value={form.country}
          onChange={(v) => set("country", v)}
          placeholder="Cambodia"
        />
      </div>
      <div className="mt-4">
        <TextAreaField
          label="Address"
          name="address"
          value={form.address}
          onChange={(v) => set("address", v)}
          placeholder="Street, city, province"
        />
      </div>
      <FormActions
        onCancel={onSuccess}
        submitLabel={isEdit ? "Save changes" : "Create guest"}
        loading={mutation.isPending}
      />
    </form>
  );
}

export default function GuestsPage() {
  const queryClient = useQueryClient();
  const [page, setPage] = useState(1);
  const [search, setSearch] = useUrlQuerySearch();
  const [country, setCountry] = useState("");
  const [formOpen, setFormOpen] = useState(false);
  const [editing, setEditing] = useState(null);
  const [deleting, setDeleting] = useState(null);
  const [detail, setDetail] = useState(null);

  const query = useQuery({
    queryKey: ["guests", page, search, country],
    queryFn: () =>
      fetchGuests({
        page,
        search: search || undefined,
        country: country || undefined,
      }),
  });

  const deleteMutation = useMutation({
    mutationFn: deleteGuest,
    onSuccess: () => {
      toast.success("Guest deleted.");
      queryClient.invalidateQueries({ queryKey: ["guests"] });
      queryClient.invalidateQueries({ queryKey: ["guests-options"] });
      setDeleting(null);
    },
    onError: (error) => toast.error(getErrorMessage(error, "Could not delete guest.")),
  });

  const columns = [
    {
      header: "Guest",
      cell: (r) => (
        <div className="flex items-center gap-3">
          <span className="flex h-10 w-10 shrink-0 items-center justify-center overflow-hidden rounded-full bg-[#7FA35C]/12 text-sm font-semibold text-[#4F7A3B]">
            {r.photo_url ? (
              <img src={r.photo_url} alt={r.full_name} className="h-full w-full object-cover" />
            ) : (
              initialsOf(r.full_name)
            )}
          </span>
          <div>
            <p className="font-medium text-[#1E2B22]">{r.full_name}</p>
            <p className="text-xs text-[#7A8677]">
              {r.nationality || "—"} · {formatDate(r.date_of_birth)}
            </p>
          </div>
        </div>
      ),
    },
    {
      header: "Contact",
      cell: (r) => (
        <div className="space-y-1">
          {r.email && (
            <p className="flex items-center gap-1.5 text-xs text-[#5E6B5A]">
              <Mail className="h-3.5 w-3.5 text-[#A8B39F]" strokeWidth={1.5} />
              {r.email}
            </p>
          )}
          {r.phone && (
            <p className="flex items-center gap-1.5 text-xs text-[#5E6B5A]">
              <Phone className="h-3.5 w-3.5 text-[#A8B39F]" strokeWidth={1.5} />
              {r.phone}
            </p>
          )}
          {!r.email && !r.phone && "—"}
        </div>
      ),
    },
    {
      header: "Country",
      cell: (r) => <span className="text-[#5E6B5A]">{r.country || "—"}</span>,
    },
    {
      header: "ID",
      cell: (r) => (
        <div>
          <p className="text-[#1E2B22]">{titleCaseGuest(r.id_type)}</p>
          <p className="text-xs text-[#7A8677]">{r.id_number || "—"}</p>
        </div>
      ),
    },
    {
      header: "Bookings",
      cell: (r) => (
        <span className="inline-flex items-center rounded-md bg-[#F1F3ED] px-2 py-1 text-xs font-medium text-[#5E6B5A]">
          {r.bookings_count ?? 0}
        </span>
      ),
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
            aria-label="Edit guest"
            className="flex h-8 w-8 items-center justify-center rounded-lg text-[#7A8677] transition-colors hover:bg-[#F1F3ED] hover:text-[#1E2B22]"
          >
            <Pencil className="h-4 w-4" strokeWidth={1.5} />
          </button>
          <button
            type="button"
            onClick={() => setDeleting(r)}
            aria-label="Delete guest"
            className="flex h-8 w-8 items-center justify-center rounded-lg text-[#7A8677] transition-colors hover:bg-[#C25B50]/10 hover:text-[#B3453A]"
          >
            <Trash2 className="h-4 w-4" strokeWidth={1.5} />
          </button>
        </div>
      ),
    },
  ];

  if (query.isError) {
    return <ErrorState message="Could not load guests." onRetry={query.refetch} />;
  }

  const { items = [], meta = {} } = query.data ?? {};

  return (
    <div>
      <PageHeader
        title="Guests"
        description="Your guest directory — contacts, passports and stay history."
      />

      <div className="mb-4 flex flex-wrap items-center gap-2.5">
        <SearchInput
          value={search}
          onChange={(v) => {
            setSearch(v);
            setPage(1);
          }}
          placeholder="Search guests…"
        />
        <DebouncedInput
          value={country}
          onChange={(v) => {
            setCountry(v);
            setPage(1);
          }}
          placeholder="Filter by country"
          className="h-10 w-44 rounded-lg border border-[#DCE3D5] bg-white px-3 text-sm text-[#1E2B22] outline-none placeholder:text-[#A8B39F] focus:border-[#7FA35C]"
        />
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
            Add guest
          </button>
        </div>
      </div>

      {query.isLoading ? (
        <LoadingState label="Loading guests…" />
      ) : (
        <>
          <DataTable
            columns={columns}
            data={items}
            loading={query.isLoading}
            emptyTitle="No guests found"
            emptyDescription="Try adjusting your filters, or add a new guest."
            onRowDoubleClick={setDetail}
            minWidth={1180}
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
        title={editing ? `Edit ${editing.full_name}` : "Add a guest"}
      >
        <GuestForm
          key={editing?.id ?? "new"}
          guest={editing}
          onSuccess={() => {
            setFormOpen(false);
            setEditing(null);
          }}
        />
      </FormModal>

      <ConfirmDialog
        open={Boolean(deleting)}
        onOpenChange={(open) => !open && setDeleting(null)}
        title={`Delete ${deleting?.full_name ?? "guest"}?`}
        description="This will permanently remove the guest record. Their bookings and reviews are kept."
        confirmLabel="Delete guest"
        loading={deleteMutation.isPending}
        onConfirm={() => deleting && deleteMutation.mutate(deleting.id)}
      />

      <DetailModal
        open={Boolean(detail)}
        onOpenChange={(open) => !open && setDetail(null)}
        title={detail ? detail.full_name : "Guest details"}
        description="Double-click a guest row to view their full record."
        record={detail}
      />
    </div>
  );
}

function titleCaseGuest(value) {
  if (!value) return "—";
  return String(value).replaceAll("_", " ").replace(/\b\w/g, (c) => c.toUpperCase());
}