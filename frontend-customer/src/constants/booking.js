export const ROOM_STATUS_LABELS = {
  available: "Available",
  occupied: "Occupied",
  maintenance: "Maintenance",
  cleaning: "Cleaning",
  out_of_service: "Out of service",
};

export const ROOM_STATUS_STYLES = {
  available: "bg-brand-100 text-brand-700",
  occupied: "bg-amber-100 text-amber-800",
  maintenance: "bg-red-100 text-red-700",
  cleaning: "bg-blue-100 text-blue-700",
  out_of_service: "bg-neutral-200 text-neutral-600",
};

export const BOOKING_STATUS_LABELS = {
  pending: "bookings.filterPending",
  confirmed: "bookings.confirmed",
  in_house: "bookings.inHouse",
  cancelled: "bookings.cancelled",
  completed: "bookings.completed",
  cancellation_requested: "bookings.cancelRequested",
};

export const BOOKING_STATUS_STYLES = {
  pending: "bg-amber-100 text-amber-800",
  confirmed: "bg-brand-100 text-brand-700",
  in_house: "bg-blue-100 text-blue-800",
  cancelled: "bg-red-100 text-red-700",
  completed: "bg-neutral-200 text-neutral-700",
  cancellation_requested: "bg-blue-100 text-blue-800",
};

export const BOOKING_SOURCE_LABELS = {
  website: "Website",
  phone: "Phone",
  walk_in: "Walk-in",
  third_party: "Third party",
};

export const MAX_GUESTS = 20;

export const BOOKING_STEPS = ["Stay details", "Guest info", "Review", "Done"];

export const BRIEF_ID_TYPES = [
  { value: "national_id", label: "National ID" },
  { value: "passport", label: "Passport" },
];
