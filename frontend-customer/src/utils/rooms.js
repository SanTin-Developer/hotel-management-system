export function slugify(text) {
  return String(text ?? "")
    .toLowerCase()
    .trim()
    .replace(/[^a-z0-9\s-]/g, "")
    .replace(/[\s_]+/g, "-")
    .replace(/-+/g, "-")
    .replace(/^-+|-+$/g, "");
}

export function roomTypeToSlug(roomType) {
  return `${slugify(roomType?.name ?? "room")}-${roomType?.id ?? ""}`;
}

export function slugToRoomTypeId(slug) {
  const match = String(slug ?? "").match(/(-(\d+))$/);
  return match ? Number(match[2]) : null;
}

export function getFallbackImage() {
  return `https://images.unsplash.com/photo-1611892440504-42a792e24d32?auto=format&fit=crop&w=1200&q=70`;
}

export function formatBedType(bedType) {
  const map = {
    king: "King bed",
    queen: "Queen bed",
    double: "Double bed",
    twin: "Twin beds",
    single: "Single bed",
    "2_double": "Two double beds",
  };
  return map[bedType] ?? "Bed";
}

export function formatCapacity(capacity) {
  return `${capacity} ${capacity === 1 ? "guest" : "guests"}`;
}