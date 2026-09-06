import { useRef, useState } from "react";
import { useQuery, useMutation, useQueryClient } from "@tanstack/react-query";
import { Link } from "react-router-dom";
import { Plus, Pencil, Trash2, ImagePlus, Layers } from "lucide-react";
import { toast } from "sonner";

import PageHeader from "@/components/PageHeader";
import DataTable from "@/components/DataTable";
import SearchInput from "@/components/SearchInput";
import useUrlQuerySearch from "@/hooks/useUrlQuerySearch";
import StatusBadge from "@/components/StatusBadge";
import Pagination from "@/components/Pagination";
import ConfirmDialog from "@/components/ConfirmDialog";
import DetailModal from "@/components/DetailModal";
import FormModal, { FormActions } from "@/components/FormModal";
import { LoadingState, ErrorState } from "@/components/States";
import { TextField, SelectField, TextAreaField, ImageField } from "@/components/form/Inputs";
import {
  fetchRooms,
  fetchRoomTypes,
  createRoom,
  updateRoom,
  deleteRoom,
  changeRoomStatus,
  uploadRoomImage,
  deleteRoomImage,
} from "@/services/api/rooms";
import { getErrorMessage, formatCurrency } from "@/lib/format";

const ROOM_STATUSES = [
  ["available", "Available"],
  ["occupied", "Occupied"],
  ["maintenance", "Maintenance"],
  ["cleaning", "Cleaning"],
  ["out_of_service", "Out of service"],
];

const EMPTY_FORM = {
  room_type_id: "",
  room_number: "",
  floor: "",
  status: "available",
  description: "",
};

function RoomForm({ room, roomTypes, onSuccess }) {
  const queryClient = useQueryClient();
  const isEdit = Boolean(room);
  const [form, setForm] = useState(
    isEdit
      ? {
          room_type_id: String(room.room_type_id),
          room_number: room.room_number,
          floor: String(room.floor),
          status: room.status,
          description: room.description ?? "",
        }
      : EMPTY_FORM,
  );
  const [image, setImage] = useState(null);
  const [removeImage, setRemoveImage] = useState(false);
  const [errors, setErrors] = useState({});

  const mutation = useMutation({
    mutationFn: async (data) => {
      const saved = isEdit
        ? await updateRoom(room.id, data)
        : await createRoom(data);

      const id = saved.id ?? room?.id;

      if (removeImage && id) {
        await deleteRoomImage(id).catch(() => {});
      }

      if (image && id) {
        await uploadRoomImage(id, image);
      }

      return saved;
    },
    onSuccess: (data) => {
      toast.success(isEdit ? "Room updated." : `Room ${data.room_number} created.`);
      queryClient.invalidateQueries({ queryKey: ["rooms"] });
      onSuccess();
    },
    onError: (error) => toast.error(getErrorMessage(error, "Could not save room.")),
  });

  function set(field, value) {
    setForm((f) => ({ ...f, [field]: value }));
    setErrors((e) => ({ ...e, [field]: undefined }));
  }

  function handleSubmit(e) {
    e.preventDefault();
    const errs = {};
    if (!form.room_type_id) errs.room_type_id = "Please choose a room type.";
    if (!form.room_number.trim()) errs.room_number = "Room number is required.";
    if (!form.floor || form.floor < 0) errs.floor = "Enter a valid floor.";
    setErrors(errs);
    if (Object.keys(errs).length) return;
    mutation.mutate({
      room_type_id: Number(form.room_type_id),
      room_number: form.room_number.trim(),
      floor: Number(form.floor),
      status: form.status,
      description: form.description || null,
    });
  }

  return (
    <form onSubmit={handleSubmit}>
      <div className="grid grid-cols-1 gap-4 sm:grid-cols-2">
        <SelectField
          label="Room type"
          name="room_type_id"
          value={form.room_type_id}
          onChange={(v) => set("room_type_id", v)}
          error={errors.room_type_id}
          required
          options={(roomTypes ?? []).map((rt) => [String(rt.id), rt.name])}
        />
        <TextField
          label="Room number"
          name="room_number"
          value={form.room_number}
          onChange={(v) => set("room_number", v)}
          error={errors.room_number}
          required
          placeholder="101"
        />
        <TextField
          label="Floor"
          name="floor"
          value={form.floor}
          onChange={(v) => set("floor", v)}
          error={errors.floor}
          required
          inputMode="numeric"
          placeholder="1"
        />
        <SelectField
          label="Status"
          name="status"
          value={form.status}
          onChange={(v) => set("status", v)}
          options={ROOM_STATUSES}
        />
      </div>
      <div className="mt-4">
        <ImageField
          label="Image"
          name="image"
          value={image}
          onChange={(v) => {
            setImage(v);
            if (v) setRemoveImage(false);
          }}
          initialUrl={isEdit ? room.image_url : undefined}
          onRemove={() => {
            setRemoveImage(true);
            setImage(null);
          }}
          hint={image ? "Uploaded when you save." : "Choose a photo for this room."}
        />
      </div>
      <div className="mt-4">
        <TextAreaField
          label="Description"
          name="description"
          value={form.description}
          onChange={(v) => set("description", v)}
          placeholder="Optional notes about this room"
        />
      </div>
      <FormActions
        onCancel={onSuccess}
        submitLabel={isEdit ? "Save changes" : "Create room"}
        loading={mutation.isPending}
      />
    </form>
  );
}

export default function RoomsPage() {
  const queryClient = useQueryClient();
  const [page, setPage] = useState(1);
  const [search, setSearch] = useUrlQuerySearch();
  const [status, setStatus] = useState("");
  const [typeFilter, setTypeFilter] = useState("");
  const [formOpen, setFormOpen] = useState(false);
  const [editing, setEditing] = useState(null);
  const [deleting, setDeleting] = useState(null);
  const [imageTargetId, setImageTargetId] = useState(null);
  const hiddenImageInput = useRef(null);
  const [detail, setDetail] = useState(null);

  const roomsQuery = useQuery({
    queryKey: ["rooms", page, search, status, typeFilter],
    queryFn: () =>
      fetchRooms({
        page,
        search: search || undefined,
        status: status || undefined,
        room_type_id: typeFilter || undefined,
      }),
  });

  const roomTypesQuery = useQuery({
    queryKey: ["room-types"],
    queryFn: fetchRoomTypes,
  });

  const deleteMutation = useMutation({
    mutationFn: deleteRoom,
    onSuccess: () => {
      toast.success("Room deleted.");
      queryClient.invalidateQueries({ queryKey: ["rooms"] });
      setDeleting(null);
    },
    onError: (error) => toast.error(getErrorMessage(error, "Could not delete room.")),
  });

  const statusMutation = useMutation({
    mutationFn: ({ id, status }) => changeRoomStatus(id, { status, note: "Status updated from admin." }),
    onSuccess: (_data, vars) => {
      toast.success(`Room status changed to ${vars.status.replaceAll("_", " ")}.`);
      queryClient.invalidateQueries({ queryKey: ["rooms"] });
    },
    onError: (error) => toast.error(getErrorMessage(error, "Could not update status.")),
  });

  function handleImageUpload(file) {
    if (!file || !imageTargetId) return;
    const id = imageTargetId;
    setImageTargetId(null);
    uploadRoomImage(id, file)
      .then(() => {
        toast.success("Room image uploaded.");
        queryClient.invalidateQueries({ queryKey: ["rooms"] });
      })
      .catch((error) => toast.error(getErrorMessage(error, "Upload failed.")));
  }

  const columns = [
    {
      header: "Room",
      cell: (r) => (
        <div className="flex items-center gap-3">
          <span className="flex h-11 w-11 shrink-0 items-center justify-center overflow-hidden rounded-lg bg-[#EEF1E9] text-[#5E6B5A]">
            {r.image_url ? (
              <img src={r.image_url} alt={`Room ${r.room_number}`} className="h-full w-full object-cover" />
            ) : (
              <span className="text-sm font-semibold">{r.room_number}</span>
            )}
          </span>
          <div>
            <p className="font-medium text-[#1E2B22]">{r.room_number}</p>
            <p className="text-xs text-[#7A8677]">Floor {r.floor}</p>
          </div>
        </div>
      ),
    },
    {
      header: "Type",
      cell: (r) => (
        <div>
          <p className="text-[#1E2B22]">{r.room_type?.name ?? "—"}</p>
          {r.room_type?.bed_type && (
            <p className="text-xs text-[#7A8677]">{r.room_type.bed_type}</p>
          )}
        </div>
      ),
    },
    {
      header: "Price / night",
      cell: (r) => (
        <span className="font-medium text-[#1E2B22]">
          {formatCurrency(r.room_type?.base_price ?? 0)}
        </span>
      ),
    },
    {
      header: "Booking count",
      accessor: "booking_items_count",
      cell: (r) => (
        <span className="inline-flex items-center rounded-md bg-[#F1F3ED] px-2 py-1 text-xs font-medium text-[#5E6B5A]">
          {r.booking_items_count ?? 0} bookings
        </span>
      ),
    },
    {
      header: "Status",
      cell: (r) => (
        <div className="flex items-center gap-2">
          <StatusBadge status={r.status} />
          <select
            value=""
            onChange={(e) => {
              if (e.target.value) statusMutation.mutate({ id: r.id, status: e.target.value });
              e.target.value = "";
            }}
            aria-label="Change status"
            className="h-7 rounded-md border border-[#DCE3D5] bg-white px-1 text-xs text-[#5E6B5A] outline-none focus:border-[#7FA35C]"
          >
            <option value="" disabled>
              Change…
            </option>
            {ROOM_STATUSES
              .filter(([v]) => v !== r.status)
              .map(([v, l]) => (
                <option key={v} value={v}>
                  {l}
                </option>
              ))}
          </select>
        </div>
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
              setImageTargetId(r.id);
              hiddenImageInput.current?.click();
            }}
            aria-label="Upload image"
            className="flex h-8 w-8 items-center justify-center rounded-lg text-[#7A8677] transition-colors hover:bg-[#F1F3ED] hover:text-[#4F7A3B]"
          >
            <ImagePlus className="h-4 w-4" strokeWidth={1.5} />
          </button>
          <button
            type="button"
            onClick={() => {
              setEditing(r);
              setFormOpen(true);
            }}
            aria-label="Edit room"
            className="flex h-8 w-8 items-center justify-center rounded-lg text-[#7A8677] transition-colors hover:bg-[#F1F3ED] hover:text-[#1E2B22]"
          >
            <Pencil className="h-4 w-4" strokeWidth={1.5} />
          </button>
          <button
            type="button"
            onClick={() => setDeleting(r)}
            aria-label="Delete room"
            className="flex h-8 w-8 items-center justify-center rounded-lg text-[#7A8677] transition-colors hover:bg-[#C25B50]/10 hover:text-[#B3453A]"
          >
            <Trash2 className="h-4 w-4" strokeWidth={1.5} />
          </button>
        </div>
      ),
    },
  ];

  if (roomsQuery.isError) {
    return <ErrorState message="Could not load rooms." onRetry={roomsQuery.refetch} />;
  }

  const roomTypes = roomTypesQuery.data?.items ?? [];
  const { items = [], meta = {} } = roomsQuery.data ?? {};

  return (
    <div>
      <PageHeader
        title="Rooms"
        description="Manage rooms, pricing and their availability status."
      />

      <div className="mb-4 flex flex-wrap items-center gap-2.5">
        <SearchInput
          value={search}
          onChange={(v) => {
            setSearch(v);
            setPage(1);
          }}
          placeholder="Search rooms…"
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
          {ROOM_STATUSES.map(([v, l]) => (
            <option key={v} value={v}>
              {l}
            </option>
          ))}
        </select>
        <select
          value={typeFilter}
          onChange={(e) => {
            setTypeFilter(e.target.value);
            setPage(1);
          }}
          className="h-10 rounded-lg border border-[#DCE3D5] bg-white px-3 text-sm text-[#5E6B5A] outline-none focus:border-[#7FA35C]"
        >
          <option value="">All types</option>
          {roomTypes.map((rt) => (
            <option key={rt.id} value={rt.id}>
              {rt.name}
            </option>
          ))}
        </select>
        <div className="ml-auto flex items-center gap-2">
          <Link
            to="/rooms/types"
            className="flex h-10 items-center gap-2 rounded-lg border border-[#DCE3D5] bg-white px-4 text-sm font-medium text-[#5E6B5A] transition-colors hover:bg-[#F1F3ED] hover:text-[#1E2B22]"
          >
            <Layers className="h-4 w-4" strokeWidth={1.5} />
            Room types
          </Link>
          <button
            type="button"
            onClick={() => {
              setEditing(null);
              setFormOpen(true);
            }}
            className="flex h-10 items-center gap-2 rounded-lg bg-[#16261F] px-4 text-sm font-medium text-[#F2F5EC] transition-all duration-200 hover:bg-[#20372C] hover:shadow-[0_0_0_3px_rgba(127,163,92,0.3)]"
          >
            <Plus className="h-4 w-4" strokeWidth={1.5} />
            Add room
          </button>
        </div>
      </div>

      {roomsQuery.isLoading ? (
        <LoadingState label="Loading rooms…" />
      ) : (
        <>
          <DataTable
            columns={columns}
            data={items}
            loading={roomsQuery.isLoading}
            emptyTitle="No rooms found"
            emptyDescription="Try adjusting your filters, or add a new room."
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
        title={editing ? `Edit room ${editing.room_number}` : "Add a new room"}
        description={
          editing
            ? "Update the room's details."
            : "Create a new room and assign it a room type."
        }
      >
        <RoomForm
          key={editing?.id ?? "new"}
          room={editing}
          roomTypes={roomTypes}
          onSuccess={() => {
            setFormOpen(false);
            setEditing(null);
          }}
        />
      </FormModal>

      <input
        ref={hiddenImageInput}
        type="file"
        accept="image/*"
        className="hidden"
        onChange={(e) => {
          handleImageUpload(e.target.files?.[0]);
          e.target.value = "";
        }}
      />

      <ConfirmDialog
        open={Boolean(deleting)}
        onOpenChange={(open) => !open && setDeleting(null)}
        title={`Delete room ${deleting?.room_number ?? ""}?`}
        description="This will permanently remove the room from your property. Bookings and history are kept."
        confirmLabel="Delete room"
        loading={deleteMutation.isPending}
        onConfirm={() => deleting && deleteMutation.mutate(deleting.id)}
      />

      <DetailModal
        open={Boolean(detail)}
        onOpenChange={(open) => !open && setDetail(null)}
        title={detail ? `Room ${detail.room_number}` : "Room details"}
        record={detail}
      />
    </div>
  );
}