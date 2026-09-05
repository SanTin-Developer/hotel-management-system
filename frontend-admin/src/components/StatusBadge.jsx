const STATUS_STYLES = {
  pending: "bg-[#D9A441]/12 text-[#A67C16] ring-[#D9A441]/25",
  confirmed: "bg-[#7FA35C]/12 text-[#4F7A3B] ring-[#7FA35C]/25",
  approved: "bg-[#7FA35C]/12 text-[#4F7A3B] ring-[#7FA35C]/25",
  paid: "bg-[#7FA35C]/12 text-[#4F7A3B] ring-[#7FA35C]/25",
  active: "bg-[#7FA35C]/12 text-[#4F7A3B] ring-[#7FA35C]/25",
  available: "bg-[#7FA35C]/12 text-[#4F7A3B] ring-[#7FA35C]/25",
  completed: "bg-[#16261F] text-[#9DBE7C] ring-[#16261F]/20",
  cancelled: "bg-[#C25B50]/10 text-[#B3453A] ring-[#C25B50]/25",
  rejected: "bg-[#C25B50]/10 text-[#B3453A] ring-[#C25B50]/25",
  failed: "bg-[#C25B50]/10 text-[#B3453A] ring-[#C25B50]/25",
  refunded: "bg-[#A8B39F]/15 text-[#5E6B5A] ring-[#A8B39F]/30",
  maintenance: "bg-[#D9A441]/12 text-[#A67C16] ring-[#D9A441]/25",
  cleaning: "bg-[#B5C9A4]/20 text-[#5E6B5A] ring-[#B5C9A4]/30",
  occupied: "bg-[#16261F]/5 text-[#1E2B22] ring-[#16261F]/15",
  out_of_service: "bg-[#C25B50]/10 text-[#B3453A] ring-[#C25B50]/25",
  inactive: "bg-[#EDE9E4] text-[#7A8677] ring-[#D4CDC4]/40",
};

const LABELS = {
  pending: "Pending",
  confirmed: "Confirmed",
  approved: "Approved",
  paid: "Paid",
  active: "Active",
  available: "Available",
  completed: "Completed",
  cancelled: "Cancelled",
  rejected: "Rejected",
  failed: "Failed",
  refunded: "Refunded",
  maintenance: "Maintenance",
  cleaning: "Cleaning",
  occupied: "Occupied",
  out_of_service: "Out of service",
  inactive: "Inactive",
};

export default function StatusBadge({ status, className = "" }) {
  const label = LABELS[status] ?? String(status).replaceAll("_", " ");
  const style = STATUS_STYLES[status] ?? "bg-[#F1F3ED] text-[#5E6B5A] ring-[#DCE3D5]/50";
  return (
    <span
      className={`inline-flex items-center gap-1.5 rounded-full px-2.5 py-1 text-xs font-medium capitalize ring-1 ring-inset ${style} ${className}`}
    >
      <span className="h-1.5 w-1.5 rounded-full bg-current" />
      {label}
    </span>
  );
}