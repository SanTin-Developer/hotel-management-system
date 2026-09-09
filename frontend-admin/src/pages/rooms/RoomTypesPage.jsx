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
import DetailModal from "@/components/DetailModal";
import FormModal, { FormActions } from "@/components/FormModal";
import { LoadingState, ErrorState } from "@/components/States";
import { TextField, SelectField, TextAreaField, ImageField } from "@/components/form/Inputs";
import {
  fetchRoomTypes,
  createRoomType,
  updateRoomType,
  deleteRoomType,
  uploadRoomTypeImages,
  deleteRoomTypeImage,
} from "@/services/api/rooms";
import { getErrorMessage, formatCurrency } from "@/lib/format";

const STATUSES = [
  ["active", "Active"],
  ["inactive", "Inactive"],
];

const BED_TYPES = [
  ["single", "Single"],
  ["double", "Double"],
  ["queen", "Queen"],
  ["king", "King"],
  ["twin", "Twin"],
  ["bunk", "Bunk"],
  ["sofa", "Sofa"],
];

const EMPTY_FORM = {
  name: "",
  name_kh: "",
  description: "",
  description_kh: "",
  capacity: "2",
  base_price: "",
  size: "",
  bed_type: "",
  status: "active",
};

function RoomTypeForm({ roomType, onSuccess }) {
  const queryClient = useQueryClient();
  const isEdit = Boolean(roomType);
  const [form, setForm] = useState(
    isEdit
      ? {
          name: roomType.name,
          name_kh: roomType.name_kh ?? "",
          description: roomType.description ?? "",
          description_kh: roomType.description_kh ?? "",
          capacity: String(roomType.capacity),
          base_price: String(roomType.base_price ?? ""),
          size: String(roomType.size ?? ""),
          bed_type: roomType.bed_type ?? "",
          status: roomType.status,
        }
      : EMPTY_FORM,
  );
  const [image, setImage] = useState([]);
  const [removeImages, setRemoveImages] = useState([]);
  const [errors, setErrors] = useState({});

  const mutation = useMutation({
    mutationFn: async (data) => {
      const saved = isEdit
        ? await updateRoomType(roomType.id, data)
        : await createRoomType(data);

      const id = saved.id ?? roomType?.id;

      if (removeImages.length > 0 && id) {
        await Promise.all(
          removeImages.map((img) => deleteRoomTypeImage(id, img.id).catch(() => {}))
        );
      }

      if (image.length > 0 && id) {
        await uploadRoomTypeImages(id, image);
      }

      return saved;
    },
    onSuccess: () => {
      toast.success(isEdit ? "Room type updated." : "Room type created.");
      queryClient.invalidateQueries({ queryKey: ["room-types"] });
      onSuccess();
    },
    onError: (error) => toast.error(getErrorMessage(error, "Could not save room type.")),
  });

  function set(field, value) {
    setForm((f) => ({ ...f, [field]: value }));
    setErrors((e) => ({ ...e, [field]: undefined }));
  }

  function handleSubmit(e) {
    e.preventDefault();
    const errs = {};
    if (!form.name.trim()) errs.name = "Name is required.";
    if (!form.capacity || Number(form.capacity) < 1) errs.capacity = "Enter a valid capacity.";
    if (form.base_price === "" || Number(form.base_price) < 0) errs.base_price = "Enter a valid price.";
    setErrors(errs);
    if (Object.keys(errs).length) return;
    mutation.mutate({
      name: form.name.trim(),
      name_kh: form.name_kh.trim() || null,
      description: form.description || null,
      description_kh: form.description_kh.trim() || null,
      capacity: Number(form.capacity),
      base_price: form.base_price ? Number(form.base_price) : null,
      size: form.size ? Number(form.size) : null,
      bed_type: form.bed_type || null,
      status: form.status,
    });
  }

  return (
    <form onSubmit={handleSubmit}>
      <div className="grid grid-cols-1 gap-4 sm:grid-cols-2">
        <TextField
          label="Name"
          name="name"
          value={form.name}
          onChange={(v) => set("name", v)}
          error={errors.name}
          required
          placeholder="Deluxe King"
        />
        <TextField
          label="Name (Khmer)"
          name="name_kh"
          value={form.name_kh}
          onChange={(v) => set("name_kh", v)}
          placeholder="បន្ទប់គេងពិសេស"
        />
        <TextField
          label="Capacity (guests)"
          name="capacity"
          value={form.capacity}
          onChange={(v) => set("capacity", v)}
          error={errors.capacity}
          required
          inputMode="numeric"
        />
        <TextField
          label="Base price / night"
          name="base_price"
          value={form.base_price}
          onChange={(v) => set("base_price", v)}
          error={errors.base_price}
          required
          inputMode="decimal"
          placeholder="150.00"
        />
        <TextField
          label="Size (m²)"
          name="size"
          value={form.size}
          onChange={(v) => set("size", v)}
          inputMode="decimal"
          placeholder="35.00"
        />
        <SelectField
          label="Bed type"
          name="bed_type"
          value={form.bed_type}
          onChange={(v) => set("bed_type", v)}
          options={BED_TYPES}
          placeholder="Select a bed type"
        />
        <SelectField
          label="Status"
          name="status"
          value={form.status}
          onChange={(v) => set("status", v)}
          options={STATUSES}
        />
      </div>
      <div className="mt-4">
        <ImageField
          label="Images"
          name="images"
          multiple
          value={image}
          onChange={(v) => {
            setImage((prev) => [...prev, ...(Array.isArray(v) ? v : [v])]);
          }}
          initialImages={isEdit ? (roomType.images ?? []) : []}
          onRemove={(img) => {
            if (img.isNew) {
              setImage((prev) => prev.filter((_, i) => `new-${i}` !== img.id));
            } else {
              setRemoveImages((prev) => [...prev, img]);
            }
          }}
          hint="Upload up to 10 images for this room type."
        />
      </div>
      <div className="mt-4">
        <TextAreaField
          label="Description"
          name="description"
          value={form.description}
          onChange={(v) => set("description", v)}
          placeholder="Amenities and highlights of this room type"
        />
      </div>
      <div className="mt-4">
        <TextAreaField
          label="Description (Khmer)"
          name="description_kh"
          value={form.description_kh}
          onChange={(v) => set("description_kh", v)}
          placeholder="ពិពណ៌នាអំពីបន្ទប់ប្រភេទនេះ"
        />
      </div>
      <FormActions
        onCancel={onSuccess}
        submitLabel={isEdit ? "Save changes" : "Create room type"}
        loading={mutation.isPending}
      />
    </form>
  );
}

export default function RoomTypesPage() {
  const queryClient = useQueryClient();
  const [page, setPage] = useState(1);
  const [search, setSearch] = useState("");
  const [formOpen, setFormOpen] = useState(false);
  const [editing, setEditing] = useState(null);
  const [deleting, setDeleting] = useState(null);
  const [detail, setDetail] = useState(null);

  const query = useQuery({
    queryKey: ["room-types", page, search],
    queryFn: () => fetchRoomTypes({ page, search: search || undefined }),
  });

  const deleteMutation = useMutation({
    mutationFn: deleteRoomType,
    onSuccess: () => {
      toast.success("Room type deleted.");
      queryClient.invalidateQueries({ queryKey: ["room-types"] });
      setDeleting(null);
    },
    onError: (error) => toast.error(getErrorMessage(error, "Could not delete room type.")),
  });

  const columns = [
    {
      header: "Room type",
      cell: (r) => (
        <div className="flex items-center gap-3">
          <span className="flex h-11 w-11 shrink-0 items-center justify-center overflow-hidden rounded-lg bg-[#EEF1E9]">
            {r.image_url ? (
              <img src={r.image_url} alt={r.name} className="h-full w-full object-cover" />
            ) : (
              <span className="text-sm font-semibold text-[#5E6B5A]">
                {r.name.slice(0, 2).toUpperCase()}
              </span>
            )}
          </span>
          <div>
            <p className="font-medium text-[#1E2B22]">{r.name}</p>
            {r.name_kh && (
              <p className="text-xs text-[#A67C16]">{r.name_kh}</p>
            )}
            <p className="text-xs text-[#7A8677]">{r.bed_type || "—"} bed</p>
          </div>
        </div>
      ),
    },
    {
      header: "Capacity",
      cell: (r) => <span>{r.capacity} guests</span>,
    },
    {
      header: "Size",
      cell: (r) => <span>{r.size ? `${r.size} m²` : "—"}</span>,
    },
    {
      header: "Price / night",
      cell: (r) => (
        <span className="font-medium text-[#1E2B22]">{formatCurrency(r.base_price)}</span>
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
            aria-label="Edit room type"
            className="flex h-8 w-8 items-center justify-center rounded-lg text-[#7A8677] transition-colors hover:bg-[#F1F3ED] hover:text-[#1E2B22]"
          >
            <Pencil className="h-4 w-4" strokeWidth={1.5} />
          </button>
          <button
            type="button"
            onClick={() => setDeleting(r)}
            aria-label="Delete room type"
            className="flex h-8 w-8 items-center justify-center rounded-lg text-[#7A8677] transition-colors hover:bg-[#C25B50]/10 hover:text-[#B3453A]"
          >
            <Trash2 className="h-4 w-4" strokeWidth={1.5} />
          </button>
        </div>
      ),
    },
  ];

  if (query.isError) {
    return <ErrorState message="Could not load room types." onRetry={query.refetch} />;
  }

  const { items = [], meta = {} } = query.data ?? {};

  return (
    <div>
      <PageHeader
        title="Room types"
        description="Define categories, pricing and capacity for your rooms."
      />

      <div className="mb-4 flex flex-wrap items-center gap-2.5">
        <SearchInput
          value={search}
          onChange={(v) => {
            setSearch(v);
            setPage(1);
          }}
          placeholder="Search room types…"
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
            Add room type
          </button>
        </div>
      </div>

      {query.isLoading ? (
        <LoadingState label="Loading room types…" />
      ) : (
        <>
          <DataTable
            columns={columns}
            data={items}
            loading={query.isLoading}
            emptyTitle="No room types found"
            emptyDescription="Add a room type to start categorising your rooms."
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
        title={editing ? `Edit ${editing.name}` : "Add a room type"}
      >
        <RoomTypeForm
          key={editing?.id ?? "new"}
          roomType={editing}
          onSuccess={() => {
            setFormOpen(false);
            setEditing(null);
          }}
        />
      </FormModal>

      <ConfirmDialog
        open={Boolean(deleting)}
        onOpenChange={(open) => !open && setDeleting(null)}
        title={`Delete ${deleting?.name ?? "room type"}?`}
        description="This will permanently remove the room type. Rooms assigned to it may need a new type."
        confirmLabel="Delete"
        loading={deleteMutation.isPending}
        onConfirm={() => deleting && deleteMutation.mutate(deleting.id)}
      />

      <DetailModal
        open={Boolean(detail)}
        onOpenChange={(open) => !open && setDetail(null)}
        title={detail ? detail.name : "Room type details"}
        record={detail}
      />
    </div>
  );
}