import { useState } from "react";
import { useQuery, useMutation, useQueryClient } from "@tanstack/react-query";
import { Plus, Pencil, Trash2 } from "lucide-react";
import { toast } from "sonner";

import PageHeader from "@/components/PageHeader";
import DataTable from "@/components/DataTable";
import SearchInput from "@/components/SearchInput";
import StatusBadge from "@/components/StatusBadge";
import Pagination from "@/components/Pagination";
import ConfirmDialog from "@/components/ConfirmDialog";
import FormModal, { FormActions } from "@/components/FormModal";
import { LoadingState, ErrorState } from "@/components/States";
import { TextField, SelectField, DateField, PasswordField, ImageField } from "@/components/form/Inputs";
import { fetchStaff, createStaff, updateStaff, deleteStaff, uploadStaffPhoto, deleteStaffPhoto } from "@/services/api/staff";
import { getErrorMessage, formatDate, initialsOf } from "@/lib/format";

const ROLES = [
  ["admin", "Admin"],
  ["manager", "Manager"],
  ["staff", "Staff"],
];

const STATUSES = [
  ["active", "Active"],
  ["inactive", "Inactive"],
];

const EMPTY_FORM = {
  name: "",
  email: "",
  password: "",
  password_confirmation: "",
  phone: "",
  role: "staff",
  employee_id: "",
  position: "",
  hire_date: "",
  status: "active",
};

function StaffForm({ member, onSuccess }) {
  const queryClient = useQueryClient();
  const isEdit = Boolean(member);
  const [form, setForm] = useState(
    isEdit
      ? {
          name: member.user?.name ?? "",
          email: member.user?.email ?? "",
          password: "",
          password_confirmation: "",
          phone: member.user?.phone ?? "",
          role: member.user?.roles?.[0]?.name ?? "staff",
          employee_id: member.employee_id ?? "",
          position: member.position ?? "",
          hire_date: member.hire_date ?? "",
          status: member.status ?? "active",
        }
      : EMPTY_FORM,
  );
  const [errors, setErrors] = useState({});
  const [photo, setPhoto] = useState(null);
  const [removePhoto, setRemovePhoto] = useState(false);

  const mutation = useMutation({
    mutationFn: async (data) => {
      const saved = isEdit
        ? await updateStaff(member.id, data)
        : await createStaff(data);

      const id = saved.id ?? member?.id;

      if (removePhoto && id) {
        await deleteStaffPhoto(id).catch(() => {});
      }

      if (photo && id) {
        await uploadStaffPhoto(id, photo);
      }

      return saved;
    },
    onSuccess: () => {
      toast.success(isEdit ? "Staff member updated." : "Staff member created.");
      queryClient.invalidateQueries({ queryKey: ["staff"] });
      onSuccess();
    },
    onError: (error) => toast.error(getErrorMessage(error, "Could not save staff member.")),
  });

  function set(field, value) {
    setForm((f) => ({ ...f, [field]: value }));
    setErrors((e) => ({ ...e, [field]: undefined }));
  }

  function handleSubmit(e) {
    e.preventDefault();
    const errs = {};
    if (!form.name.trim()) errs.name = "Name is required.";
    if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(form.email)) errs.email = "Enter a valid email.";
    if (!isEdit && form.password.length < 8) {
      errs.password = "Password must be at least 8 characters.";
    }
    if (form.password && form.password !== form.password_confirmation) {
      errs.password_confirmation = "Passwords do not match.";
    }
    if (!isEdit && !form.password_confirmation) {
      errs.password_confirmation = "Please confirm the password.";
    }
    if (!form.position.trim()) errs.position = "Position is required.";
    if (!form.hire_date) errs.hire_date = "Hire date is required.";
    setErrors(errs);
    if (Object.keys(errs).length) return;

    const payload = {
      name: form.name.trim(),
      email: form.email.trim(),
      phone: form.phone || null,
      role: form.role,
      position: form.position.trim(),
      hire_date: form.hire_date,
      status: form.status,
    };
    if (isEdit) {
      payload.employee_id = form.employee_id.trim();
    }
    if (form.password) {
      payload.password = form.password;
      payload.password_confirmation = form.password_confirmation;
    }

    mutation.mutate(payload);
  }

  return (
    <form onSubmit={handleSubmit}>
      <div className="grid grid-cols-1 gap-4 sm:grid-cols-2">
        <TextField
          label="Full name"
          name="name"
          value={form.name}
          onChange={(v) => set("name", v)}
          error={errors.name}
          required
          placeholder="Dara Men"
        />
        <TextField
          label="Email"
          name="email"
          type="email"
          value={form.email}
          onChange={(v) => set("email", v)}
          error={errors.email}
          required
          placeholder="dara@hotel.com"
        />
        {!isEdit && (
          <PasswordField
            label="Password"
            name="password"
            value={form.password}
            onChange={(v) => set("password", v)}
            error={errors.password}
            required
            placeholder="At least 8 characters"
            autoComplete="new-password"
          />
        )}
        {!isEdit && (
          <PasswordField
            label="Confirm password"
            name="password_confirmation"
            value={form.password_confirmation}
            onChange={(v) => set("password_confirmation", v)}
            error={errors.password_confirmation}
            required
            placeholder="Repeat the password"
            autoComplete="new-password"
          />
        )}
        <TextField
          label="Phone"
          name="phone"
          value={form.phone}
          onChange={(v) => set("phone", v)}
          placeholder="+855 12 345 678"
        />
        <SelectField
          label="Role"
          name="role"
          value={form.role}
          onChange={(v) => set("role", v)}
          options={ROLES}
        />
        <ImageField
          label="Photo"
          name="photo"
          value={photo}
          onChange={(v) => {
            setPhoto(v);
            if (v) setRemovePhoto(false);
          }}
          initialUrl={isEdit ? member.photo_url : undefined}
          onRemove={() => {
            setRemovePhoto(true);
            setPhoto(null);
          }}
          circular
          hint={photo ? "Uploaded when you save." : "Choose a profile photo."}
        />
        <TextField
          label="Position"
          name="position"
          value={form.position}
          onChange={(v) => set("position", v)}
          error={errors.position}
          required
          placeholder="Front Desk Manager"
        />
        {isEdit ? (
          <TextField
            label="Employee ID"
            name="employee_id"
            value={form.employee_id}
            onChange={(v) => set("employee_id", v)}
            error={errors.employee_id}
          />
        ) : (
          <TextField
            label="Employee ID"
            name="employee_id"
            value="Auto-generated on create"
            disabled
          />
        )}
        <DateField
          label="Hire date"
          name="hire_date"
          value={form.hire_date}
          onChange={(v) => set("hire_date", v)}
          error={errors.hire_date}
          required
          max={new Date().toISOString().slice(0, 10)}
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
        submitLabel={isEdit ? "Save changes" : "Create staff member"}
        loading={mutation.isPending}
      />
    </form>
  );
}

export default function StaffPage() {
  const queryClient = useQueryClient();
  const [page, setPage] = useState(1);
  const [search, setSearch] = useState("");
  const [status, setStatus] = useState("");
  const [formOpen, setFormOpen] = useState(false);
  const [editing, setEditing] = useState(null);
  const [deleting, setDeleting] = useState(null);

  const query = useQuery({
    queryKey: ["staff", page, search, status],
    queryFn: () =>
      fetchStaff({
        page,
        search: search || undefined,
        status: status || undefined,
      }),
  });

  const deleteMutation = useMutation({
    mutationFn: deleteStaff,
    onSuccess: () => {
      toast.success("Staff member deleted.");
      queryClient.invalidateQueries({ queryKey: ["staff"] });
      setDeleting(null);
    },
    onError: (error) => toast.error(getErrorMessage(error, "Could not delete staff member.")),
  });

  const columns = [
    {
      header: "Staff member",
      cell: (r) => (
        <div className="flex items-center gap-3">
          <span className="flex h-10 w-10 shrink-0 items-center justify-center overflow-hidden rounded-full bg-[#16261F] text-sm font-semibold text-[#9DBE7C]">
            {r.photo_url ? (
              <img src={r.photo_url} alt={r.user?.name} className="h-full w-full object-cover" />
            ) : (
              initialsOf(r.user?.name)
            )}
          </span>
          <div>
            <p className="font-medium text-[#1E2B22]">{r.user?.name}</p>
            <p className="text-xs text-[#7A8677]">
              {r.employee_id} · {r.user?.email}
            </p>
          </div>
        </div>
      ),
    },
    {
      header: "Position",
      cell: (r) => (
        <div>
          <p className="text-[#1E2B22]">{r.position ?? "—"}</p>
          <span className="mt-0.5 inline-flex items-center rounded-md bg-[#7FA35C]/12 px-2 py-0.5 text-[11px] font-medium capitalize text-[#4F7A3B]">
            {r.user?.roles?.[0]?.name ?? "staff"}
          </span>
        </div>
      ),
    },
    {
      header: "Phone",
      cell: (r) => <span className="text-[#5E6B5A]">{r.user?.phone ?? "—"}</span>,
    },
    {
      header: "Hired",
      cell: (r) => <span className="text-[#5E6B5A]">{formatDate(r.hire_date)}</span>,
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
            aria-label="Edit staff member"
            className="flex h-8 w-8 items-center justify-center rounded-lg text-[#7A8677] transition-colors hover:bg-[#F1F3ED] hover:text-[#1E2B22]"
          >
            <Pencil className="h-4 w-4" strokeWidth={1.5} />
          </button>
          <button
            type="button"
            onClick={() => setDeleting(r)}
            aria-label="Delete staff member"
            className="flex h-8 w-8 items-center justify-center rounded-lg text-[#7A8677] transition-colors hover:bg-[#C25B50]/10 hover:text-[#B3453A]"
          >
            <Trash2 className="h-4 w-4" strokeWidth={1.5} />
          </button>
        </div>
      ),
    },
  ];

  if (query.isError) {
    return <ErrorState message="Could not load staff." onRetry={query.refetch} />;
  }

  const { items = [], meta = {} } = query.data ?? {};

  return (
    <div>
      <PageHeader
        title="Staff"
        description="Your team — accounts, roles and their hire details."
      />

      <div className="mb-4 flex flex-wrap items-center gap-2.5">
        <SearchInput
          value={search}
          onChange={(v) => {
            setSearch(v);
            setPage(1);
          }}
          placeholder="Search staff…"
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
            Invite staff
          </button>
        </div>
      </div>

      {query.isLoading ? (
        <LoadingState label="Loading staff…" />
      ) : (
        <>
          <DataTable
            columns={columns}
            data={items}
            loading={query.isLoading}
            emptyTitle="No staff found"
            emptyDescription="Invite your first team member to get started."
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
        title={editing ? `Edit ${editing.user?.name ?? "staff member"}` : "Invite a staff member"}
        description={editing ? "Update role and details." : "Creates a login account for the new team member."}
      >
        <StaffForm
          key={editing?.id ?? "new"}
          member={editing}
          onSuccess={() => {
            setFormOpen(false);
            setEditing(null);
          }}
        />
      </FormModal>

      <ConfirmDialog
        open={Boolean(deleting)}
        onOpenChange={(open) => !open && setDeleting(null)}
        title={`Delete ${deleting?.user?.name ?? "staff member"}?`}
        description="Their login will be removed. Historical records are kept."
        confirmLabel="Delete"
        loading={deleteMutation.isPending}
        onConfirm={() => deleting && deleteMutation.mutate(deleting.id)}
      />
    </div>
  );
}