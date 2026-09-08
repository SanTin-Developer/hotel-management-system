import { useState } from "react";
import { useQuery, useMutation, useQueryClient } from "@tanstack/react-query";
import { Plus, Pencil, Trash2, Sparkles } from "lucide-react";
import { toast } from "sonner";

import PageHeader from "@/components/PageHeader";
import DataTable from "@/components/DataTable";
import SearchInput from "@/components/SearchInput";
import Pagination from "@/components/Pagination";
import ConfirmDialog from "@/components/ConfirmDialog";
import DetailModal from "@/components/DetailModal";
import FormModal, { FormActions } from "@/components/FormModal";
import { LoadingState, ErrorState } from "@/components/States";
import { TextField, TextAreaField } from "@/components/form/Inputs";
import {
  fetchAmenities,
  createAmenity,
  updateAmenity,
  deleteAmenity,
} from "@/services/api/amenities";
import { getErrorMessage, formatDate, titleCase } from "@/lib/format";

const EMPTY_FORM = {
  name: "",
  name_kh: "",
  description: "",
  description_kh: "",
  icon: "",
};

function AmenityForm({ amenity, onSuccess }) {
  const queryClient = useQueryClient();
  const isEdit = Boolean(amenity);
  const [form, setForm] = useState(
    isEdit
      ? {
          name: amenity.name,
          name_kh: amenity.name_kh ?? "",
          description: amenity.description ?? "",
          description_kh: amenity.description_kh ?? "",
          icon: amenity.icon ?? "",
        }
      : EMPTY_FORM,
  );
  const [errors, setErrors] = useState({});

  const mutation = useMutation({
    mutationFn: (data) =>
      isEdit ? updateAmenity(amenity.id, data) : createAmenity(data),
    onSuccess: () => {
      toast.success(isEdit ? "Amenity updated." : "Amenity created.");
      queryClient.invalidateQueries({ queryKey: ["amenities"] });
      onSuccess();
    },
    onError: (error) => toast.error(getErrorMessage(error, "Could not save amenity.")),
  });

  function set(field, value) {
    setForm((f) => ({ ...f, [field]: value }));
    setErrors((e) => ({ ...e, [field]: undefined }));
  }

  function handleSubmit(e) {
    e.preventDefault();
    const errs = {};
    if (!form.name.trim()) errs.name = "Name is required.";
    setErrors(errs);
    if (Object.keys(errs).length) return;
    mutation.mutate({
      name: form.name.trim(),
      name_kh: form.name_kh.trim() || null,
      description: form.description.trim() || null,
      description_kh: form.description_kh.trim() || null,
      icon: form.icon.trim() || null,
    });
  }

  return (
    <form onSubmit={handleSubmit}>
      <div className="grid grid-cols-1 gap-4">
        <TextField
          label="Name"
          name="name"
          value={form.name}
          onChange={(v) => set("name", v)}
          error={errors.name}
          required
          placeholder="Free Wi-Fi"
        />
        <TextField
          label="Name (Khmer)"
          name="name_kh"
          value={form.name_kh}
          onChange={(v) => set("name_kh", v)}
          placeholder="វ៉ាយហ្វាយឥតគិតថ្លៃ"
        />
        <TextField
          label="Icon"
          name="icon"
          value={form.icon}
          onChange={(v) => set("icon", v)}
          hint="Optional icon key, e.g. wifi, parking, pool."
          placeholder="wifi"
        />
        <TextAreaField
          label="Description"
          name="description"
          value={form.description}
          onChange={(v) => set("description", v)}
          placeholder="Describe what guests can expect."
        />
        <TextAreaField
          label="Description (Khmer)"
          name="description_kh"
          value={form.description_kh}
          onChange={(v) => set("description_kh", v)}
          placeholder="ពិពណ៌នាអំពីអ្វីដែលភ្ញៀវអាចរំពឹងបាន។"
        />
      </div>
      <FormActions
        onCancel={onSuccess}
        submitLabel={isEdit ? "Save changes" : "Create amenity"}
        loading={mutation.isPending}
      />
    </form>
  );
}

export default function AmenitiesPage() {
  const queryClient = useQueryClient();
  const [page, setPage] = useState(1);
  const [search, setSearch] = useState("");
  const [formOpen, setFormOpen] = useState(false);
  const [editing, setEditing] = useState(null);
  const [deleting, setDeleting] = useState(null);
  const [detail, setDetail] = useState(null);

  const query = useQuery({
    queryKey: ["amenities", page, search],
    queryFn: () =>
      fetchAmenities({
        page,
        search: search || undefined,
      }),
  });

  const deleteMutation = useMutation({
    mutationFn: deleteAmenity,
    onSuccess: () => {
      toast.success("Amenity deleted.");
      queryClient.invalidateQueries({ queryKey: ["amenities"] });
      setDeleting(null);
    },
    onError: (error) => toast.error(getErrorMessage(error, "Could not delete amenity.")),
  });

  const columns = [
    {
      header: "Amenity",
      cell: (r) => (
        <div className="flex items-center gap-3">
          <span className="flex h-10 w-10 shrink-0 items-center justify-center rounded-lg bg-[#D9A441]/12 text-[#A67C16]">
            <Sparkles className="h-4 w-4" strokeWidth={1.5} />
          </span>
          <div>
            <p className="font-semibold text-[#1E2B22]">{r.name}</p>
            {r.name_kh && (
              <p className="text-xs text-[#A67C16]">{r.name_kh}</p>
            )}
            {r.icon && (
              <p className="text-xs text-[#7A8677]">{titleCase(r.icon)}</p>
            )}
          </div>
        </div>
      ),
    },
    {
      header: "Description",
      cell: (r) => (
        <div className="max-w-md">
          <p className="truncate text-sm text-[#5E6B5A]">
            {r.description || "—"}
          </p>
          {r.description_kh && (
            <p className="truncate text-xs text-[#A67C16]">{r.description_kh}</p>
          )}
        </div>
      ),
    },
    {
      header: "Rooms",
      cell: (r) => (
        <span className="inline-flex items-center rounded-md bg-[#F1F3ED] px-2 py-1 text-xs font-medium text-[#5E6B5A]">
          {r.rooms_count ?? 0} rooms
        </span>
      ),
    },
    {
      header: "Created",
      cell: (r) => <span className="text-sm text-[#7A8677]">{formatDate(r.created_at)}</span>,
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
            aria-label="Edit amenity"
            className="flex h-8 w-8 items-center justify-center rounded-lg text-[#7A8677] transition-colors hover:bg-[#F1F3ED] hover:text-[#1E2B22]"
          >
            <Pencil className="h-4 w-4" strokeWidth={1.5} />
          </button>
          <button
            type="button"
            onClick={() => setDeleting(r)}
            aria-label="Delete amenity"
            className="flex h-8 w-8 items-center justify-center rounded-lg text-[#7A8677] transition-colors hover:bg-[#C25B50]/10 hover:text-[#B3453A]"
          >
            <Trash2 className="h-4 w-4" strokeWidth={1.5} />
          </button>
        </div>
      ),
    },
  ];

  if (query.isError) {
    return <ErrorState message="Could not load amenities." onRetry={query.refetch} />;
  }

  const { items = [], meta = {} } = query.data ?? {};

  return (
    <div>
      <PageHeader
        title="Amenities"
        description="Facilities and perks offered across your rooms."
      />

      <div className="mb-4 flex flex-wrap items-center gap-2.5">
        <SearchInput
          value={search}
          onChange={(v) => {
            setSearch(v);
            setPage(1);
          }}
          placeholder="Search amenities…"
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
            Add amenity
          </button>
        </div>
      </div>

      {query.isLoading ? (
        <LoadingState label="Loading amenities…" />
      ) : (
        <>
          <DataTable
            columns={columns}
            data={items}
            loading={query.isLoading}
            emptyTitle="No amenities found"
            emptyDescription="Add an amenity to make your rooms more appealing."
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
        title={editing ? `Edit amenity ${editing.name}` : "Create an amenity"}
      >
        <AmenityForm
          key={editing?.id ?? "new"}
          amenity={editing}
          onSuccess={() => {
            setFormOpen(false);
            setEditing(null);
          }}
        />
      </FormModal>

      <ConfirmDialog
        open={Boolean(deleting)}
        onOpenChange={(open) => !open && setDeleting(null)}
        title={`Delete amenity ${deleting?.name ?? ""}?`}
        description="This will remove the amenity. It cannot be deleted while still assigned to rooms."
        confirmLabel="Delete amenity"
        loading={deleteMutation.isPending}
        onConfirm={() => deleting && deleteMutation.mutate(deleting.id)}
      />

      <DetailModal
        open={Boolean(detail)}
        onOpenChange={(open) => !open && setDetail(null)}
        title={detail?.name ?? "Amenity details"}
        record={detail}
      />
    </div>
  );
}